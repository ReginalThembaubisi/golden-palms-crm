<?php

declare(strict_types=1);

namespace GoldenPalms\CRM\Services;

use Illuminate\Database\Capsule\Manager as DB;
use GoldenPalms\CRM\Helpers\Helper;

class WorkflowEngine
{
    /**
     * Process workflows for a trigger
     */
    public static function processTrigger(string $triggerType, string $entityType, int $entityId, array $data = []): void
    {
        $workflows = DB::table('workflows')
            ->where('trigger_type', $triggerType)
            ->where('is_active', 1)
            ->get();

        foreach ($workflows as $workflow) {
            // Check if conditions are met
            if (self::checkConditions($workflow, $entityType, $entityId, $data)) {
                self::executeWorkflow($workflow, $entityType, $entityId);
            }
        }
    }

    private static function checkConditions($workflow, string $entityType, int $entityId, array $data): bool
    {
        $conditions = json_decode($workflow->trigger_conditions, true) ?? [];

        if (empty($conditions)) {
            return true; // No conditions = always execute
        }

        // Get entity data
        $entity = self::getEntity($entityType, $entityId);
        if (!$entity) {
            return false;
        }

        // Merge entity data with provided data
        $allData = array_merge((array)$entity, $data);

        // Example condition checking
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? '';

            $fieldValue = $allData[$field] ?? null;

            // Special handling for date-based conditions
            if ($field === 'hours_since_created' && isset($allData['created_at'])) {
                $created = new \DateTime($allData['created_at']);
                $now = new \DateTime();
                $hoursSinceCreation = ($now->getTimestamp() - $created->getTimestamp()) / 3600;
                $fieldValue = $hoursSinceCreation;
            }

            if (!self::evaluateCondition($fieldValue, $operator, $value)) {
                return false;
            }
        }

        return true;
    }

    private static function evaluateCondition($fieldValue, string $operator, $value): bool
    {
        switch ($operator) {
            case 'equals':
                return $fieldValue == $value;
            case 'not_equals':
                return $fieldValue != $value;
            case 'contains':
                return str_contains((string)$fieldValue, (string)$value);
            case 'greater_than':
                return (float)$fieldValue > (float)$value;
            case 'less_than':
                return (float)$fieldValue < (float)$value;
            case 'is_empty':
                return empty($fieldValue);
            case 'is_not_empty':
                return !empty($fieldValue);
            default:
                return false;
        }
    }

    private static function getEntity(string $entityType, int $entityId)
    {
        switch ($entityType) {
            case 'lead':
                return DB::table('leads')->where('id', $entityId)->first();
            case 'booking':
                return DB::table('bookings')
                    ->leftJoin('guests', 'bookings.guest_id', '=', 'guests.id')
                    ->select('bookings.*', 'guests.email as guest_email', 'guests.first_name', 'guests.last_name')
                    ->where('bookings.id', $entityId)
                    ->first();
            case 'guest':
                return DB::table('guests')->where('id', $entityId)->first();
            default:
                return null;
        }
    }

    private static function executeWorkflow($workflow, string $entityType, int $entityId): void
    {
        // Create execution record
        $executionId = DB::table('workflow_executions')->insertGetId([
            'workflow_id' => $workflow->id,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'status' => 'running',
            'created_at' => Helper::now(),
        ]);

        try {
            $actions = json_decode($workflow->actions, true) ?? [];

            foreach ($actions as $action) {
                self::executeAction($action, $entityType, $entityId);
            }

            // Mark as completed
            DB::table('workflow_executions')
                ->where('id', $executionId)
                ->update([
                    'status' => 'completed',
                    'executed_at' => Helper::now(),
                ]);

            // Update workflow stats
            DB::table('workflows')
                ->where('id', $workflow->id)
                ->increment('execution_count', 1);
            DB::table('workflows')
                ->where('id', $workflow->id)
                ->update(['last_executed_at' => Helper::now()]);

        } catch (\Exception $e) {
            // Mark as failed
            DB::table('workflow_executions')
                ->where('id', $executionId)
                ->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'executed_at' => Helper::now(),
                ]);
        }
    }

    private static function executeAction(array $action, string $entityType, int $entityId): void
    {
        $actionType = $action['type'] ?? '';

        switch ($actionType) {
            case 'send_email':
                self::actionSendEmail($action, $entityType, $entityId);
                break;
            case 'assign_user':
                self::actionAssignUser($action, $entityType, $entityId);
                break;
            case 'update_status':
                self::actionUpdateStatus($action, $entityType, $entityId);
                break;
            case 'create_task':
                self::actionCreateTask($action, $entityType, $entityId);
                break;
            case 'add_note':
                self::actionAddNote($action, $entityType, $entityId);
                break;
        }
    }

    private static function actionSendEmail(array $action, string $entityType, int $entityId): void
    {
        $entity = self::getEntity($entityType, $entityId);
        if (!$entity) {
            return;
        }

        // Determine recipient
        $to = $action['to'] ?? null;
        if ($to === 'lead_email' && isset($entity->email)) {
            $to = $entity->email;
        } elseif ($to === 'guest_email' && isset($entity->guest_email)) {
            $to = $entity->guest_email;
        }

        if (!$to) {
            return;
        }

        // Get template
        $templateName = $action['template'] ?? 'default';
        
        // Use EmailService to send (if template exists)
        // For now, just log the action
        error_log("Workflow: Would send email to {$to} using template {$templateName}");
        
        // TODO: Integrate with EmailService when templates are ready
    }

    private static function actionAssignUser(array $action, string $entityType, int $entityId): void
    {
        $userId = $action['user_id'] ?? null;
        if (!$userId) {
            return;
        }

        $table = null;
        switch ($entityType) {
            case 'lead':
                $table = 'leads';
                break;
            case 'booking':
                $table = 'bookings';
                break;
        }

        if ($table) {
            DB::table($table)
                ->where('id', $entityId)
                ->update(['assigned_to' => $userId]);
        }
    }

    private static function actionUpdateStatus(array $action, string $entityType, int $entityId): void
    {
        $status = $action['status'] ?? null;
        if (!$status) {
            return;
        }

        $table = null;
        switch ($entityType) {
            case 'lead':
                $table = 'leads';
                break;
            case 'booking':
                $table = 'bookings';
                break;
        }

        if ($table) {
            DB::table($table)
                ->where('id', $entityId)
                ->update(['status' => $status]);
        }
    }

    private static function actionCreateTask(array $action, string $entityType, int $entityId): void
    {
        // Create task (if tasks table exists)
        // For now, just log
        error_log("Workflow: Would create task for {$entityType} #{$entityId}");
    }

    private static function actionAddNote(array $action, string $entityType, int $entityId): void
    {
        $note = $action['note'] ?? '';
        if (empty($note)) {
            return;
        }

        $table = null;
        switch ($entityType) {
            case 'lead':
                $table = 'leads';
                break;
            case 'booking':
                $table = 'bookings';
                break;
        }

        if ($table) {
            $entity = DB::table($table)->where('id', $entityId)->first();
            if ($entity) {
                $existingNotes = $entity->notes ?? '';
                $timestamp = date('Y-m-d H:i:s');
                $newNotes = $existingNotes . "\n\n[{$timestamp}] " . $note;

                DB::table($table)
                    ->where('id', $entityId)
                    ->update(['notes' => $newNotes]);
            }
        }
    }
}


