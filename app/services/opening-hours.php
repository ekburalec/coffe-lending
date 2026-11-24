<?php
declare(strict_types=1);

function openingHours(?DateTimeImmutable $now = null): array
{
    $now ??= new DateTimeImmutable('now', new DateTimeZone(appConfig('timezone')));
    $now = $now->setTimezone(new DateTimeZone(appConfig('timezone')));
    $hours = listRecords('hours');
    $day = $now->format('N');
    $today = findRecord('hours', $day);
    $special = false;
    foreach (listRecords('exceptions', true) as $exception) {
        if ($exception['date'] === $now->format('Y-m-d')) {
            $today = $exception;
            $special = true;
            break;
        }
    }
    $lines = [];
    foreach ($hours as $row) $lines[] = $row['name'] . ': ' . (!empty($row['closed']) ? 'выходной' : $row['opens'] . '–' . $row['closes']);
    $patterns = array_unique(array_map(fn (array $row): string => !empty($row['closed']) ? 'выходной' : $row['opens'] . '–' . $row['closes'], $hours));
    $weekly = count($patterns) === 1 ? 'Ежедневно ' . reset($patterns) : implode('; ', $lines);
    $todayText = !$today || !empty($today['closed']) ? 'Сегодня закрыто' : 'Сегодня ' . $today['opens'] . '–' . $today['closes'];
    if ($special && !empty($today['note'])) $todayText .= '. ' . $today['note'];
    return ['weekly' => $weekly, 'today' => $todayText, 'special' => $special, 'rows' => $hours];
}
