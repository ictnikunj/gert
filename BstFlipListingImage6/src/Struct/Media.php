<?php
/**
 * NOTICE OF LICENSE
 *
 * @copyright  Copyright (c) 21.10.2020 brainstation GbR
 * @author     Marco Becker<marco@brainstation.de>
 */
namespace BstFlipListingImage6\Struct;

use Shopware\Core\Framework\Struct\Struct;

class Media extends Struct
{
    /** @var object */
    protected $media;

    /**
     * @return mixed
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * @param mixed $media
     */
    public function setMedia($media)
    {
        $this->media = $media;

        return $this;
    }
}
