<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Content\FormElement;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;

class FormElement extends Entity implements FormElementInterface
{
    protected string $type;
    protected string $id;
    protected string $mediaType = 'documents';
    protected ?string $name = null;
    protected ?string $mapping = null;
    protected $value = null;
    protected array $behaviour = [];
    protected array $conditions = [];
    protected array $options = [];
    protected ?bool $useImageSelection = false;
    protected ?bool $useCustomTemplate = false;
    protected string $gridSizeLg = "12";

    /* Date & Time properties */
    protected int $timeStep = 0;
    protected int $timeMin = 0;
    protected int $timeMax = 0;
    protected string $dateMin = "";
    protected string $dateMax = "";
    protected int $dateStep = 0;
    protected array $dateExclude = [];

    public function __construct(array $formElement) {
        foreach ($formElement as $k => $v) {
            if (isset($this->$k)) {
                if (is_string($this->$k)) {
                    $this->$k = (string) $v;
                } elseif (is_int($this->$k)) {
                    $this->$k = (int) $v;
                } elseif (is_bool($this->$k)) {
                    $this->$k = (bool) $v;
                } else {
                    $this->$k = $v;
                }
            } else {
                $this->$k = $v;
            }
        }
    }

    /**
     * @return int
     */
    public function getTimeStep(): int
    {
        return $this->timeStep;
    }

    /**
     * @return int
     */
    public function getTimeMin(): int
    {
        return $this->timeMin;
    }

    /**
     * @return int
     */
    public function getTimeMax(): int
    {
        return $this->timeMax;
    }

    /**
     * @return string
     */
    public function getDateMin(): string
    {
        return $this->dateMin;
    }

    /**
     * @return string
     */
    public function getDateMax(): string
    {
        return $this->dateMax;
    }

    /**
     * @return int
     */
    public function getDateStep(): int
    {
        return $this->dateStep;
    }

    /**
     * @return array
     */
    public function getDateExclude(): array
    {
        return $this->dateExclude;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function getAppointmentOptions(): array
    {
        return [];

        $timeInterval = new \DateInterval(sprintf("PT%sM", $this->timeStep));
        $timeMin = new \DateTime();
        $timeMax = clone($timeMin);
        $timeRange = new \DatePeriod(
            $timeMin->setTime((int) $this->timeMin,0),
            $timeInterval,
            $timeMax->setTime((int) $this->timeMax,0)
        );

        $dateInterval = new \DateInterval(sprintf("P%sD", $this->dateStep));
        $dateMin = new \DateTime($this->dateMin);
        $dateMax = new \DateTime($this->dateMax);
        $dateRange = new \DatePeriod($dateMin, $dateInterval, $dateMax);

        $optgroup = [];
        foreach ($dateRange as $date) {
            if (in_array($date->format("w"), $this->dateExclude)) {
                /*$optgroup[] = ['label' => $date->format('Y-m-d')];*/
                continue;
            }

            $options = [];
            foreach ($timeRange as $time) {
                $options[] = [
                    'value' => sprintf(
                        "%sT%s",
                        $date->format('Y-m-d'),
                        $time->format('H:i')
                    ),
                    'disabled' => false
                ];
            }

            $optgroup[] = [
                'label' => $date->format('Y-m-d'),
                'options' => $options,
            ];
        }

        return $optgroup;
    }

    /**
     * @return string|null
     */
    public function getMapping(): ?string
    {
        return $this->mapping;
    }

    /**
     * @param string|null $mapping
     */
    public function setMapping(?string $mapping): void
    {
        $this->mapping = $mapping;
    }

    /**
     * @return null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param $value
     */
    public function setValue($value = null): void
    {
        $this->value = $value;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getMediaType(): string
    {
        return $this->mediaType ?: 'documents';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getBehaviour(): array
    {
        return $this->behaviour;
    }

    /**
     * @param array $behaviour
     */
    public function setBehaviour(array $behaviour): void
    {
        $this->behaviour = $behaviour;
    }

    /**
     * @return array
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * @param array $conditions
     */
    public function setConditions(array $conditions): void
    {
        $this->conditions = $conditions;
    }

    /**
     * @return bool|null
     */
    public function getUseImageSelection(): ?bool
    {
        return $this->useImageSelection;
    }

    /**
     * @param bool|null $useImageSelection
     */
    public function setUseImageSelection(?bool $useImageSelection): void
    {
        $this->useImageSelection = $useImageSelection;
    }

    /**
     * @return bool|null
     */
    public function getUseCustomTemplate(): ?bool
    {
        return $this->useCustomTemplate;
    }

    /**
     * @param bool|null $useCustomTemplate
     */
    public function setUseCustomTemplate(?bool $useCustomTemplate): void
    {
        $this->useCustomTemplate = $useCustomTemplate;
    }

    public function getBehaviourClass(): string
    {
        if (empty($this->behaviour)) {
                return sprintf("col col-lg-%s", $this->gridSizeLg);
        }

        $txt = "";

        foreach ($this->behaviour as $breakpoint => $behaviour) {
            if (isset($behaviour['order']) && (int)$behaviour['order'] !== 0) {
                $txt .= sprintf("order-%s-%d ", $breakpoint, $behaviour['order']);
            }

            if (isset($behaviour['width']) && (int)$behaviour['width'] !== -1) {
                $txt .= sprintf("col-%s-%d ", $breakpoint, $behaviour['width']);
            } else {
                continue;
            }

            if (isset($behaviour['visible']) && empty($this->conditions)) {
                $txt .= sprintf("d-%s-%s ", $breakpoint, $behaviour['visible'] ? 'block' : 'none');
            }
        }

        return trim(str_replace(["base-","-0"], "", $txt));
    }

    public function getImageClass(): string
    {
        if ($this->useImageSelection && !$this->useCustomTemplate) {
            return "moorl-form-builder-image-selection";
        }

        return "";
    }
}
