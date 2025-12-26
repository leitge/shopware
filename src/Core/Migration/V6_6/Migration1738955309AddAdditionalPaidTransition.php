<?php declare(strict_types=1);

namespace Shopware\Core\Migration\V6_6;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

#[Package('core')]
class Migration1738955309AddAdditionalPaidTransition extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1738955309;
    }

    public function update(Connection $connection): void
    {
        $stateMachineId = (string) $connection->fetchOne(
            'SELECT `id` FROM `state_machine`
             WHERE `technical_name` = :technical_name LIMIT 1',
            ['technical_name' => OrderTransactionStates::STATE_MACHINE]
        );

        $statePaidPartiallyId = (string) $connection->fetchOne(
            'SELECT `id` FROM `state_machine_state`
             WHERE `state_machine_id` = :stateMachineId
               AND `technical_name` = :technicalName
             LIMIT 1',
            [
                'stateMachineId' => $stateMachineId,
                'technicalName'  => 'paid_partially',
            ]
        );

        $statePaidId = (string) $connection->fetchOne(
            'SELECT `id` FROM `state_machine_state`
             WHERE `state_machine_id` = :stateMachineId
               AND `technical_name` = :technicalName
             LIMIT 1',
            [
                'stateMachineId' => $stateMachineId,
                'technicalName'  => 'paid',
            ]
        );

        $remindedId = (string) $connection->fetchOne(
            'SELECT `id` FROM `state_machine_state`
             WHERE `state_machine_id` = :stateMachineId
               AND `technical_name` = :technicalName
             LIMIT 1',
            [
                'stateMachineId' => $stateMachineId,
                'technicalName'  => 'reminded',
            ]
        );

        $existingTransition1 = $connection->fetchOne(
            'SELECT COUNT(*) FROM `state_machine_transition`
             WHERE `state_machine_id` = :stateMachineId
               AND `from_state_id` = :fromStateId
               AND `to_state_id` = :toStateId
               AND `action_name` = :actionName',
            [
                'stateMachineId' => $stateMachineId,
                'fromStateId'    => $remindedId,
                'toStateId'      => $statePaidPartiallyId,
                'actionName'     => 'paid_partially',
            ]
        );
        if ((int)$existingTransition1 === 0) {
            $connection->insert('state_machine_transition', [
                'id' => Uuid::randomBytes(),
                'state_machine_id' => $stateMachineId,
                'action_name' => 'paid_partially',
                'from_state_id' => $remindedId,
                'to_state_id' => $statePaidPartiallyId,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }

        $existingTransition2 = $connection->fetchOne(
            'SELECT COUNT(*) FROM `state_machine_transition`
             WHERE `state_machine_id` = :stateMachineId
               AND `from_state_id` = :fromStateId
               AND `to_state_id` = :toStateId
               AND `action_name` = :actionName',
            [
                'stateMachineId' => $stateMachineId,
                'fromStateId'    => $remindedId,
                'toStateId'      => $statePaidId,
                'actionName'     => 'paid',
            ]
        );
        if ((int)$existingTransition2 === 0) {
            $connection->insert('state_machine_transition', [
                'id' => Uuid::randomBytes(),
                'state_machine_id' => $stateMachineId,
                'action_name' => 'paid',
                'from_state_id' => $remindedId,
                'to_state_id' => $statePaidId,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }
        
        $existingTransition3 = $connection->fetchOne(
            'SELECT COUNT(*) FROM `state_machine_transition`
             WHERE `state_machine_id` = :stateMachineId
               AND `from_state_id` = :fromStateId
               AND `to_state_id` = :toStateId
               AND `action_name` = :actionName',
            [
                'stateMachineId' => $stateMachineId,
                'fromStateId'    => $statePaidPartiallyId,
                'toStateId'      => $statePaidId,
                'actionName'     => 'paid',
            ]
        );
        if ((int)$existingTransition3 === 0) {
            $connection->insert('state_machine_transition', [
                'id' => Uuid::randomBytes(),
                'state_machine_id' => $stateMachineId,
                'action_name' => 'paid',
                'from_state_id' => $statePaidPartiallyId,
                'to_state_id' => $statePaidId,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
