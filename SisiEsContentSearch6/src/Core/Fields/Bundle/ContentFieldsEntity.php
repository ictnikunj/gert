<?php

declare(strict_types=1);

namespace Sisi\SisiEsContentSearch6\Core\Fields\Bundle;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

/**
 * Class ContentFieldsEntity
 * @package Sisi\SisiEsContentSearch6\Core\Fields\Bundle
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ContentFieldsEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $shop;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $language;

    /**
     * @var string
     */
    protected $display;

    /**
     * @var string
     */
    protected $tokenizer;

    /**
     * @var int
     */
    protected $minedge;

    /**
     * @var int
     */
    protected $edge;

    /**
     * @var string
     */
    protected $filter1;

    /**
     * @var string
     */
    protected $filter2;

    /**
     * @var string
     */
    protected $filter3;

    /**
     * @var string
     */
    protected $stemming;

    /**
     * @var string
     */
    protected $stemmingstop;

    /**
     * @var string
     */
    protected $stop;

    /**
     * @var int
     */
    protected $maxhits;

    /**
     * @var string
     */
    protected $format;

    /**
     * @var string
     */
    protected $pattern;


    /**
     * @return string
     */
    public function getShop(): string
    {
        if ($this->shop === null) {
            $this->shop = '';
        }
        return $this->shop;
    }

    /**
     * @param string $shop
     */
    public function setShop(string $shop): void
    {
        $this->shop = $shop;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        if ($this->label === null) {
            $this->label = '';
        }
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        if ($this->language === null) {
            $this->language = '';
        }
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    /**
     * @return string
     */
    public function getDisplay(): string
    {
        if ($this->display === null) {
            $this->display = '';
        }
        return $this->display;
    }

    /**
     * @param string $display
     */
    public function setDisplay(string $display): void
    {
        $this->display = $display;
    }

    /**
     * @return string
     */
    public function getTokenizer(): string
    {
        if ($this->tokenizer === null) {
            $this->tokenizer = '';
        }
        return $this->tokenizer;
    }

    /**
     * @param string $tokenizer
     */
    public function setTokenizer(string $tokenizer): void
    {
        $this->tokenizer = $tokenizer;
    }

    /**
     * @return int
     */
    public function getMinedge(): int
    {
        return $this->minedge;
    }

    /**
     * @param int $minedge
     */
    public function setMinedge(int $minedge): void
    {
        $this->minedge = $minedge;
    }

    /**
     * @return int
     */
    public function getEdge(): int
    {
        return $this->edge;
    }

    /**
     * @param int $edge
     */
    public function setEdge(int $edge): void
    {
        $this->edge = $edge;
    }

    /**
     * @return string
     */
    public function getFilter1(): string
    {
        if ($this->filter1 === null) {
             $this->filter1 = '';
        }
        return $this->filter1;
    }

    /**
     * @param string $filter1
     */
    public function setFilter1(string $filter1): void
    {
        $this->filter1 = $filter1;
    }

    /**
     * @return string
     */
    public function getFilter2(): string
    {
        if ($this->filter2 === null) {
            $this->filter2 = '';
        }
        return $this->filter2;
    }

    /**
     * @param string $filter2
     */
    public function setFilter2(string $filter2): void
    {
        $this->filter2 = $filter2;
    }

    /**
     * @return string
     */
    public function getFilter3(): string
    {
        if ($this->filter3 === null) {
            $this->filter3 = '';
        }
        return $this->filter3;
    }

    /**
     * @param string $filter3
     */
    public function setFilter3(string $filter3): void
    {
        $this->filter3 = $filter3;
    }

    /**
     * @return string
     */
    public function getStemming(): string
    {
        if ($this->stemming === null) {
            $this->stemming = '';
        }
        return $this->stemming;
    }

    /**
     * @param string $stemming
     */
    public function setStemming(string $stemming): void
    {
        $this->stemming = $stemming;
    }

    /**
     * @return string
     */
    public function getStemmingstop(): string
    {
        if ($this->stemmingstop === null) {
            $this->stemmingstop = '';
        }
        return $this->stemmingstop;
    }

    /**
     * @param string $stemmingstop
     */
    public function setStemmingstop(string $stemmingstop): void
    {
        $this->stemmingstop = $stemmingstop;
    }

    /**
     * @return string
     */
    public function getStop(): string
    {
        if ($this->stop === null) {
            $this->stop = '';
        }
        return $this->stop;
    }

    /**
     * @param string $stop
     */
    public function setStop(string $stop): void
    {
        $this->stop = $stop;
    }

    /**
     * @return int
     */
    public function getMaxhits(): int
    {
        return $this->maxhits;
    }

    /**
     * @param int $maxhits
     */
    public function setMaxhits(int $maxhits): void
    {
        $this->maxhits = $maxhits;
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        if ($this->format === null) {
            $this->format = '';
        }
        return $this->format;
    }

    /**
     * @param string $format
     */
    public function setFormat(string $format): void
    {
        $this->format = $format;
    }

    /**
     * @return string
     */
    public function getPattern(): string
    {
        if ($this->pattern === null) {
            $this->pattern = '';
        }
        return $this->pattern;
    }

    /**
     * @param string $pattern
     */
    public function setPattern(string $pattern): void
    {
        $this->pattern = $pattern;
    }
}
