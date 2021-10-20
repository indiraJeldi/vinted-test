<?php

namespace App\Controller;

use SplFileObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DiscountCalculationController extends AbstractController
{
    const MaxAmount = 10.00;
    const FreeShipping = 3;
    const CalendarFreeShippingFlagFALSE = FALSE;
    const CalendarFreeShippingFlagTRUE = TRUE;

    /**
     *
     */
    #[Route('/discount/calculation', 'discount_calculation')]
    final public function index()
    {
        $packageSizeArray = $this->getParameter('packageSizeArray');

        $smallPackagePrize = $this->getParameter('smallPackagePrice');

        $prizeRange = ['LP' => ['S' => '1.50', 'M' => 4.90, 'L' => 6.90],
                        'MR' => ['S' => 2.00, 'M' => 3.00, 'L' => 4.00]];

        $this->getSmallShippingPrice($smallPackagePrize);

        $calendarFreeShipping = self::CalendarFreeShippingFlagFALSE;

        $lpLargeShippingCountPerMonth = 0;

        $yearMonthDate = [];

        $finalData = [];

        $file = $this->fileObject('../public/uploads/test.txt', 'r');

        while ( !$file->eof() ) {

            $lineData = $file->fgets() . PHP_EOL; // getData from the text file

            $data = explode(' ', $lineData);

            $dateOfPurchase = isset($data[2])?$this->issetData($data[0]) : "";

            $size = isset($data[1])? $this->issetData($data[1]) : "";

            $provider = isset($data[2])?$this->issetData($data[2]) : "";

            //check the date of the month
            $monthYear = $this->formatDate($dateOfPurchase);

            if ((isset($size, $dateOfPurchase) && !empty($size) &&
                    in_array($size, $packageSizeArray, true)) ||
                    in_array($dateOfPurchase, $yearMonthDate, true) === false)
            {
                // Get the date of purchase and check if month and year already exists
                $yearMonthDate = $this->checkYearMonthPurchaseExists($monthYear, $yearMonthDate);

                if ($yearMonthDate[$monthYear] === $monthYear) {
                    $yearMonthDate[$monthYear] = 0;
                }

                if ('S' === $size) {
                    $reducedAmount = $this->reducedAmount($prizeRange[$provider][$size], $smallPackagePrize[0]);
                    $totalValue =  $this->monthlyDiscountTillDate($reducedAmount, $yearMonthDate[$monthYear]);

                    if ($totalValue <= self::MaxAmount) {
                        $data[] = $smallPackagePrize[0];
                        $data[] = $reducedAmount;

                    } else {
                        $totalValue = $this->reducedAmount(self::MaxAmount, $yearMonthDate[$monthYear]);

                        if ($totalValue <= $reducedAmount) {
                            $reducedAmount = $this->reducedAmount($prizeRange[$provider][$size], $totalValue);
                            $data[] = $reducedAmount;
                            $data[] = $totalValue;
                        }
                    }

                    $yearMonthDate[$monthYear] =  $this->monthlyDiscountTillDate($reducedAmount,
                                                    $yearMonthDate[$monthYear]);

                } elseif ('L' === $size && 'LP' === $provider) {

                    if (self::FreeShipping === $lpLargeShippingCountPerMonth && !$calendarFreeShipping) {

                        $calendarMonthReducedAmount = $prizeRange[$provider][$size];

                        //check month & year of the count
                        $data[] = 0.00;

                        $data[] = $calendarMonthReducedAmount;

                        $yearMonthDate[$monthYear] =  $this->monthlyDiscountTillDate($calendarMonthReducedAmount,
                                                        $yearMonthDate[$monthYear]);

                        $calendarFreeShipping = self::CalendarFreeShippingFlagTRUE;
                    } else {
                        //  echo "L and other Providers </br>";
                        $data[] = $prizeRange[$provider][$size];
                        $data[] = '--';
                    }

                    ++$lpLargeShippingCountPerMonth;
                } else {
                    if (in_array($size, $packageSizeArray, true)) {

                        // echo "M and other Providers </br>";
                        $data[] = $prizeRange[$provider][$size];
                        $data[] = '--';
                    } elseif (isset($size) && !empty($size) && !in_array($size, $packageSizeArray, true)) {
                        $data[] = 'Ignored';
                    }
                }
            }
            $finalData[] = $data;
        }
        $file = null;
        $this->writeToFile($finalData);
        return $this->json("Check the testOutPut.txt file");
    }

    /**
     * @param $finalData
     */
    public function writeToFile($finalData)
    {
        $file = $this->fileObject('../public/uploads/testOutPut.txt', 'w+');
        foreach ($finalData as $data) {
            foreach ($data as $values) {
                $file->fwrite(trim($values)." ");
            }
            $file->fwrite("\n");
        }
    }
    /**
     * @param string $date
     * @return string
     */
    final public function formatDate($date)
    {
        $split = explode('-', $date);
        if (isset($split[1]) && !empty($split[1])) {
            $date = $split[0] . '-' . $split[1];
        }
        return trim($date);
    }

    /**
     * @param $prizeRange
     * @param $packagePrize
     * @return mixed
     */
    final public function reducedAmount($prizeRange, $packagePrize)
    {
        return ($prizeRange - $packagePrize);
    }

    /**
     * @param $reducedPrice
     * @param $monthlyDiscount
     * @return mixed
     */
    final public function monthlyDiscountTillDate ($reducedPrice, $monthlyDiscount )
    {
        return ($reducedPrice + $monthlyDiscount);
    }

    /**
     * @param $monthYear
     * @param $yearMonthDate
     * @return mixed
     */
    final public function checkYearMonthPurchaseExists($monthYear, $yearMonthDate )
    {
        if (in_array($monthYear, (array)$yearMonthDate, true) === false &&
            !isset($yearMonthDate[$monthYear]))
        {
            $yearMonthDate[$monthYear] = $monthYear;
        }
        return $yearMonthDate;
    }

    /**
     * @param $yearMonthDate
     * @param $monthYear
     * @return int
     */
    final public function initiateMonthlyDiscount(&$yearMonthDate, $monthYear) {
        return  ($yearMonthDate[$monthYear] === $monthYear) ? $yearMonthDate[$monthYear] = 0 : $yearMonthDate;
    }

    /**
     * @param $data
     * @return string|null
     */
    final public function issetData($data)
    {
        return !empty($data) ? trim($data) : null;
    }

    /**
     * @param $fileName
     * @param $mode
     * @return SplFileObject
     */
    final public function fileObject($fileName, $mode)
    {
        return new SplFileObject($fileName, $mode);
    }

    /**
     * @param $shippingPrice
     */
    final public function getSmallShippingPrice($shippingPrice)
    {
       return asort($shippingPrice);
    }

}
