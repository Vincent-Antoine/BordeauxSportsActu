<?php

namespace App\Controller;

use App\Service\CalendarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use IntlDateFormatter;
use App\Repository\EvenementRepository;




class CalendarController extends AbstractController
{
    #[Route('/calendrier', name: 'app_calendar')]
public function index(Request $request, CalendarService $calendarService, EvenementRepository $evenementRepository): Response
{
    $month = (int) $request->query->get('month', date('n'));
    $year = (int) $request->query->get('year', date('Y'));

    if ($month < 1) {
        $month = 12;
        $year--;
    } elseif ($month > 12) {
        $month = 1;
        $year++;
    }

    $calendarData = $calendarService->getMonthData($year, $month);
    $currentDate = \DateTime::createFromFormat('!Y-n', "$year-$month");

    // Récupération des événements du mois
    $start = (clone $currentDate)->setTime(0, 0);
    $end = (clone $currentDate)->modify('last day of this month')->setTime(23, 59, 59);

    $evenements = $evenementRepository->createQueryBuilder('e')
        ->where('e.date BETWEEN :start AND :end')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->getQuery()
        ->getResult();

    // Regrouper les événements par date (format 'Y-m-d')
    $evenementsParDate = [];
    foreach ($evenements as $event) {
        $dateStr = $event->getDate()->format('Y-m-d');
        $evenementsParDate[$dateStr][] = $event;
    }

    $formatter = new \IntlDateFormatter('fr_FR', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE, null, null, 'MMMM yyyy');
    $formattedTitle = ucfirst($formatter->format($currentDate));

    return $this->render('calendar/index.html.twig', [
        'calendar' => $calendarData,
        'calendarTitle' => $formattedTitle,
        'currentDate' => $currentDate,
        'prevMonth' => $month - 1,
        'nextMonth' => $month + 1,
        'prevYear' => $month === 1 ? $year - 1 : $year,
        'nextYear' => $month === 12 ? $year + 1 : $year,
        'evenementsParDate' => $evenementsParDate,
    ]);

}

    #[Route('/calendrier/day/{date}', name: 'app_calendar_day')]
public function showDay(string $date, EvenementRepository $evenementRepository): Response
{
    $dateTime = \DateTime::createFromFormat('Y-m-d', $date);

    if (!$dateTime || $dateTime->format('Y-m-d') !== $date) {
        throw $this->createNotFoundException('Date invalide.');
    }

    $startOfDay = (clone $dateTime)->setTime(0, 0, 0);
    $endOfDay = (clone $dateTime)->setTime(23, 59, 59);

    $evenements = $evenementRepository->createQueryBuilder('e')
        ->where('e.date BETWEEN :start AND :end')
        ->setParameter('start', $startOfDay)
        ->setParameter('end', $endOfDay)
        ->orderBy('e.date', 'ASC')
        ->getQuery()
        ->getResult();

    return $this->render('calendar/day.html.twig', [
        'date' => $dateTime,
        'evenements' => $evenements,
    ]);
}


    
}
