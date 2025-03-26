<?php

namespace App\Controller;

use App\Service\CalendarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CalendarController extends AbstractController
{
    #[Route('/calendar', name: 'app_calendar')]
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

        return $this->render('calendar/index.html.twig', [
            'calendar' => $calendarData,
            'prevMonth' => $month - 1,
            'nextMonth' => $month + 1,
            'prevYear' => $month === 1 ? $year - 1 : $year,
            'nextYear' => $month === 12 ? $year + 1 : $year,
        ]);
    }
    #[Route('/calendar/day/{date}', name: 'app_calendar_day')]
    public function showDay(string $date): Response
    {
        // Exemple de contenu — à adapter selon ton besoin (ex: affichage d'événements)
        return $this->render('calendar/day.html.twig', [
            'date' => $date,
        ]);
    }
}
