<?php
declare(strict_types=1);

function landingController(): void
{
    header('Cache-Control: no-cache');
    render('landing', landingData());
}
