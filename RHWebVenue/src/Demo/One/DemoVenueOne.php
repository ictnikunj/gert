<?php declare(strict_types=1);

namespace RHWeb\Venue\Demo\One;

use RHWeb\ThemeFeatures\Core\System\DataInterface;
use RHWeb\ThemeFeatures\Core\System\DataExtension;

class DemoVenueOne extends DataExtension implements DataInterface
{
    public function getPluginName(): string
    {
        return 'RHWebVenue';
    }

    public function getCreatedAt(): string
    {
        return '2036-11-11 00:00:00.000';
    }

    public function getName(): string
    {
        return 'venueOne';
    }

    public function getPath(): string
    {
        return __DIR__;
    }
}
