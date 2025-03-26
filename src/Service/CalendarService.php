<?php

namespace App\Service;

class CalendarService
{
    public function getMonthData(int $year, int $month): array
    {
        $date = \DateTime::createFromFormat('Y-n-j', "$year-$month-1");
        $startDay = (int) $date->format('N'); // 1 (lundi) à 7 (dimanche)
        $daysInMonth = (int) $date->format('t');

        return [
            'monthName' => $date->format('F'),
            'year' => $year,
            'month' => $month,
            'daysInMonth' => $daysInMonth,
            'startDay' => $startDay, // pour positionner le 1er jour
        ];
    }
}
