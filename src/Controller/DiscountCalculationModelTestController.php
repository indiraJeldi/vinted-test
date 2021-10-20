<?php

namespace App\Controller;

use SplFileObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DiscountCalculationModelTestController extends AbstractController
{
    const MaxAmount = 10.00;
    const FreeShipping = 3;
    const CalendarFreeShippingFlagFALSE = FALSE;
    const CalendarFreeShippingFlagTRUE = TRUE;
    /**
     * @var array
     */
    private $_data = array();
    private $_packageSizeArray = ['L', 'S', 'M'];
    private $_prizeRange = ['LP' => ['S' => 1.50, 'M' => 4.90, 'L' => 6.90],
        'MR' => ['S' => 2.00, 'M' => 3.00, 'L' => 4.00]];
    private $_smallPackagePrice = [1.5, 2.00];
    private $_finalData = array();
    private $_yearMonthDate = array();
    private $_calendarShippingFlag = self::CalendarFreeShippingFlagFALSE;
    private $_lpLargeShippingCountPerMonth = 0;
    private $_monthYear;

    public function __construct() {
        $this->_data;
        $this->_packageSizeArray;
        $this->_prizeRange;
        $this->_smallPackagePrice;
        $this->_finalData;
        $this->_yearMonthDate;
        $this->_calendarShippingFlag;
        $this->_lpLargeShippingCountPerMonth;
        $this->_monthYear;
    }
    /**
     * @return Response
     */
    #[Route('/discount/calculation', 'discount_calculation')]
    final public function index()
    {
        $this->getSmallShippingPrice();
        $file = $this->fileObject('../public/uploads/test.txt', 'r');
        /** @var TYPE_NAME $yearMonthDate */


        while (!$file->eof()) {
            $lineData = $file->fgets() . PHP_EOL; // getData from the text file
            $this->_data = explode(' ', $lineData);
            $dateOfPurchase = isset($this->_data[2])?$this->isDataEmpty($this->_data[0]): "";
            $size = isset($this->_data[1])? $this->isDataEmpty($this->_data[1]): "";
            $provider = isset($this->_data[2])?$this->isDataEmpty($this->_data[2]): "";

            //check the date of the month
            $this->_monthYear = $this->formatDate($dateOfPurchase);
            if ((isset($size, $dateOfPurchase) && !empty($size) &&
                    in_array($size, $this->_packageSizeArray, true)) ||
                in_array($dateOfPurchase, $this->_yearMonthDate, true) === false) {

                // Get the date of purchase and check if month and year already exists
                $this->_yearMonthDate = $this->checkYearMonthPurchaseExists($this->_monthYear);

                if ($this->_yearMonthDate[$this->_monthYear] === $this->_monthYear) {
                    $this->_yearMonthDate[$this->_monthYear] = 0;
                }
                switch($size) {
                    case 'S' :  $reducedAmount = $this->reducedAmount($this->_prizeRange[$provider][$size], $this->getSmallShippingPrice());
                                $totalValue =  $this->monthlyDiscountTillDate($reducedAmount);

                                if ($totalValue <= self::MaxAmount) {
                                    $data[] = $this->getSmallShippingPrice();
                                    $data[] = $reducedAmount;
                                    $this->_yearMonthDate[$this->_monthYear] =  $this->monthlyDiscountTillDate($reducedAmount);
                                } else {
                                    $totalValue = $this->reducedAmount(self::MaxAmount, $totalValue);
                                    if ($totalValue <= $reducedAmount) {
                                        $reducedAmount = $this->reducedAmount($this->_prizeRange[$provider][$size], $totalValue);
                                        $data[] = $reducedAmount;
                                        $data[] = $totalValue;
                                        $this->_yearMonthDate[$this->_monthYear] =  $this->monthlyDiscountTillDate($reducedAmount);
                                    }
                                }
                                break;
                    case 'L' : if (self::FreeShipping === $this->_lpLargeShippingCountPerMonth && !$this->_calendarShippingFlag && 'LP' === $provider) {
                                $calendarMonthReducedAmount = $this->_prizeRange[$provider][$size];
                                //check month & year of the count
                                $data[] = 0.00;
                                $data[] = $calendarMonthReducedAmount;
                                $this->_yearMonthDate[$this->_monthYear] =  $this->monthlyDiscountTillDate($calendarMonthReducedAmount, $this->_yearMonthDate[$this->_monthYear]);
                                $this->_calendarShippingFlag = self::CalendarFreeShippingFlagTRUE;
                                } else {
                                    //  echo "L and other Providers </br>";
                                    $data[] = $this->_prizeRange[$provider][$size];
                                    $data[] = '--';
                                }
                                ++$this->_lpLargeShippingCountPerMonth;
                                break;
                    default:     if (in_array($size, $this->_packageSizeArray, true)) {
                                // echo "M and other Providers </br>";
                                    $data[] = $this->_prizeRange[$provider][$size];
                                    $data[] = '--';
                                } elseif (isset($size) && !empty($size) && !in_array($size, $this->_packageSizeArray)) {
                                    $data[] = 'Ignored';
                                }
                                break;
                }
                //$finalData[] = $data;
              /* if ('L' === $size && 'LP' === $provider) {
                    if (self::FreeShipping === $this->_lpLargeShippingCountPerMonth && !$this->_calendarShippingFlag) {
                        $calendarMonthReducedAmount = $prizeRange[$provider][$size];
                        //check month & year of the count
                        $data[] = 0.00;
                        $data[] = $calendarMonthReducedAmount;
                        $yearMonthDate[$monthYear] =  $this->monthlyDiscountTillDate($calendarMonthReducedAmount, $yearMonthDate[$monthYear]);
                        $this->_calendarShippingFlag = self::CalendarFreeShippingFlagTRUE;
                    } else {
                        //  echo "L and other Providers </br>";
                        $data[] = $prizeRange[$provider][$size];
                        $data[] = '--';
                    }
                    ++$this->_lpLargeShippingCountPerMonth;
                } else {
                   if (in_array($size, $packageSizeArray, true)) {
                       // echo "M and other Providers </br>";
                       $data[] = $prizeRange[$provider][$size];
                       $data[] = '--';
                   } elseif (isset($size) && !empty($size) && !in_array($size, $packageSizeArray)) {
                       $data[] = 'Ignored';
                   }
               }*/
            }
            $this->_finalData[] = $data;
        }
        $file = null;
        $this->writeToFile($this->_finalData);
        return $this->json("Read the testOutPut.txt");

    }

    public function smallSizeDiscountCalculation($provider, $size) {
        $reducedAmount = $this->reducedAmount($this->_prizeRange[$provider][$size], $this->getSmallShippingPrice());
        $totalValue =  $this->monthlyDiscountTillDate($reducedAmount);

        if ($totalValue <= self::MaxAmount) {
            $data[] = $this->getSmallShippingPrice();
            $data[] = $reducedAmount;
            $this->_yearMonthDate[$this->_monthYear] =  $this->monthlyDiscountTillDate($reducedAmount);
        } else {
            $totalValue = $this->reducedAmount(self::MaxAmount, $this->_yearMonthDate[$this->_monthYear]);
            if ($totalValue <= $reducedAmount) {
                $reducedAmount = $this->reducedAmount($this->_prizeRange[$provider][$size], $totalValue);
                $data[] = $reducedAmount;
                $data[] = $totalValue;
                $this->_yearMonthDate[$this->_monthYear] =  $this->monthlyDiscountTillDate($reducedAmount);
            }
        }
    }
    /**
     * @param $finalData
     */
    public function writeToFile($finalData){
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
    final public function reducedAmount($prizeRange, $packagePrize) {
        return ($prizeRange - $packagePrize);
    }

    /**
     * @param $reducedPrice
     * @return mixed
     */
    final public function monthlyDiscountTillDate ($reducedPrice ){
        return ($reducedPrice + $this->_yearMonthDate[$this->_monthYear]);
    }

    /**
     * @param $monthYear
     * @param $yearMonthDate
     * @return mixed
     */
    final public function checkYearMonthPurchaseExists() {
        if (in_array($this->_monthYear, $this->_yearMonthDate, true) === false &&
            !isset($this->_yearMonthDate[$this->_monthYear])) {
            $this->_yearMonthDate[$this->_monthYear] = $this->_monthYear;
        }
        return $this->_yearMonthDate;
    }

    /**
     * @param $this->_yearMonthDate
     * @param $monthYear
     * @return int
     */
    final public function initiateMonthlyDiscount() {
       return  ($this->_yearMonthDate[$this->_monthYear] === $this->_monthYear) ? $this->_yearMonthDate[$this->_monthYear] = 0 : $this->_yearMonthDate;
    }

    /**
     * @param $data
     * @return string|null
     */
    final public function isDataEmpty($data) {
        return !empty($data) ? trim($data) : null;
    }

    /**
     * @param $fileName
     * @param $mode
     * @return SplFileObject
     */
    final public function fileObject($fileName, $mode) {
        return new SplFileObject($fileName, $mode);
    }

    /**
     * @param $shippingPrice
     */
    final public function getSmallShippingPrice() {
        return asort($this->_smallPackagePrice);
    }

}
