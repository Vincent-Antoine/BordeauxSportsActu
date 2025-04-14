<?php

namespace App\Controller;

use App\Service\CalendarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use IntlDateFormatter;


class CalendarController extends AbstractController
{
    #[Route('/calendrier', name: 'app_calendar')]
public function index(Request $request, CalendarService $calendarService): Response
{
    $month = (int) $request->query->get('month', date('n'));
    $year = (int) $request->query->get('year', date('Y'));

    // Ajuste les débordements de mois
    if ($month < 1) {
        $month = 12;
        $year--;
    } elseif ($month > 12) {
        $month = 1;
        $year++;
    }

    $calendarData = $calendarService->getMonthData($year, $month);
    $currentDate = \DateTime::createFromFormat('!Y-n', "$year-$month");

    // ✅ Format du titre en français
    $formatter = new IntlDateFormatter(
        'fr_FR',
        IntlDateFormatter::LONG,
        IntlDateFormatter::NONE,
        null,
        null,
        'MMMM yyyy' // ex: "avril 2025"
    );

    $formattedTitle = ucfirst($formatter->format($currentDate));

    return $this->render('calendar/index.html.twig', [
        'calendar' => $calendarData,
        'calendarTitle' => $formattedTitle,
        'currentDate' => $currentDate,
        'prevMonth' => $month - 1,
        'nextMonth' => $month + 1,
        'prevYear' => $month === 1 ? $year - 1 : $year,
        'nextYear' => $month === 12 ? $year + 1 : $year,
    ]);
}

    #[Route('/calendrier/day/{date}', name: 'app_calendar_day')]
    public function showDay(string $date): Response
    {
        return $this->render('calendar/day.html.twig', [
            'date' => $date,
        ]);
    }
}
