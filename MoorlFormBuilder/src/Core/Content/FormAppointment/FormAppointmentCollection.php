<?php declare(strict_types=1);

namespace MoorlFormBuilder\Core\Content\FormAppointment;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                       add(FormAppointmentEntity $entity)
 * @method void                       set(string $key, FormAppointmentEntity $entity)
 * @method FormAppointmentEntity[]    getIterator()
 * @method FormAppointmentEntity[]    getElements()
 * @method FormAppointmentEntity|null get(string $key)
 * @method FormAppointmentEntity|null first()
 * @method FormAppointmentEntity|null last()
 */
class FormAppointmentCollection extends EntityCollection
{
    public function isBlocked(string $start): bool
    {
        $start = new \DateTimeImmutable($start);

        foreach ($this as $appointmentEntity) {

            if ($appointmentEntity->getStart()->getTimestamp() === $start->getTimestamp()) {
                return true;
            }
        }

        return false;
    }

    protected function getExpectedClass(): string
    {
        return FormAppointmentEntity::class;
    }
}
