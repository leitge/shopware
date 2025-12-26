<?php declare(strict_types=1);

namespace Shopware\Core\Migration\Test\V6_6;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Migration\V6_6\Migration1738955309AddAdditionalPaidTransition;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

class Migration1738955309AddAdditionalPaidTransitionTest extends TestCase
{
    use KernelTestBehaviour;

    public function testMigrationAddsExpectedTransitions(): void
    {
        $connection = $this->getContainer()->get(Connection::class);

        $stateMachineId = (string)$connection->fetchOne(
            'SELECT `id` FROM `state_machine` WHERE `technical_name` = "order_transaction.state" LIMIT 1'
        );

        $remindedId = (string)$connection->fetchOne(
            'SELECT `id` FROM `state_machine_state`
             WHERE `state_machine_id` = :stateMachineId AND `technical_name` = "reminded" LIMIT 1',
            ['stateMachineId' => $stateMachineId]
        );

        $statePaidPartiallyId = (string)$connection->fetchOne(
            'SELECT `id` FROM `state_machine_state`
             WHERE `state_machine_id` = :stateMachineId AND `technical_name` = "paid_partially" LIMIT 1',
            ['stateMachineId' => $stateMachineId]
        );

        $statePaidId = (string)$connection->fetchOne(
            'SELECT `id` FROM `state_machine_state`
             WHERE `state_machine_id` = :stateMachineId AND `technical_name` = "paid" LIMIT 1',
            ['stateMachineId' => $stateMachineId]
        );

        $connection->executeUpdate(
            'DELETE FROM state_machine_transition
             WHERE state_machine_id = :stateMachineId
               AND action_name IN ("paid_partially", "paid")
               AND ((from_state_id = :remindedId AND to_state_id IN (:statePaidPartiallyId, :statePaidId))
                    OR (from_state_id = :statePaidPartiallyId AND to_state_id = :statePaidId))',
            [
                'stateMachineId'      => $stateMachineId,
                'remindedId'          => $remindedId,
                'statePaidPartiallyId'=> $statePaidPartiallyId,
                'statePaidId'         => $statePaidId,
            ]
        );

        $migration = new Migration1738955309AddAdditionalPaidTransition();
        $migration->update($connection);

        $countCase1 = (int)$connection->fetchOne(
            'SELECT COUNT(*) FROM state_machine_transition
             WHERE state_machine_id = :stateMachineId
               AND action_name = "paid_partially"
               AND from_state_id = :remindedId
               AND to_state_id = :statePaidPartiallyId',
            [
                'stateMachineId'      => $stateMachineId,
                'remindedId'          => $remindedId,
                'statePaidPartiallyId'=> $statePaidPartiallyId,
            ]
        );
        $this->assertGreaterThanOrEqual(1, $countCase1, 'Expected a transition from reminded to paid_partially with action "paid_partially" to be added.');

        $countCase2 = (int)$connection->fetchOne(
            'SELECT COUNT(*) FROM state_machine_transition
             WHERE state_machine_id = :stateMachineId
               AND action_name = "paid"
               AND from_state_id = :remindedId
               AND to_state_id = :statePaidId',
            [
                'stateMachineId' => $stateMachineId,
                'remindedId'     => $remindedId,
                'statePaidId'    => $statePaidId,
            ]
        );
        $this->assertGreaterThanOrEqual(1, $countCase2, 'Expected a transition from reminded to paid with action "paid" to be added.');

        $countCase3 = (int)$connection->fetchOne(
            'SELECT COUNT(*) FROM state_machine_transition
             WHERE state_machine_id = :stateMachineId
               AND action_name = "paid"
               AND from_state_id = :statePaidPartiallyId
               AND to_state_id = :statePaidId',
            [
                'stateMachineId'      => $stateMachineId,
                'statePaidPartiallyId'=> $statePaidPartiallyId,
                'statePaidId'         => $statePaidId,
            ]
        );
        $this->assertGreaterThanOrEqual(1, $countCase3, 'Expected a transition from paid_partially to paid with action "paid" to be added.');
    }
}
