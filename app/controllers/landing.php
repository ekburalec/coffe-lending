<?php
declare(strict_types=1);

function landingController(): void
{
    header('Cache-Control: no-cache');
    $data = landingData();
    $data['seo'] = landingSeo($data);
    render('landing', $data);
}
