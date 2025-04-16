<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Disp;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\MapsHelper;

/**
 * Class OrderMergeService
 * 
 * This service handles the merging and sequencing of orders in a delivery system.
 * It manages parent-child relationships between orders, handles order dispatch assignments,
 * and maintains the proper sequence of deliveries within a vehicle's route.
 *
 * Key Concepts:
 * - Parent Order: The main order in a sequence that other orders are linked to
 * - Child Order: An order that is linked to a parent order in a sequence
 * - Dispatch: Assignment of an order to a vehicle with a specific sequence number
 * - Run: A collection of orders assigned to a vehicle for delivery
 *
 * @package App\Services
 */
class OrderMergeService
{
    /**
     * Process a sequence of orders for dispatch assignment
     * 
     * This method handles the sequential processing of multiple orders, where each order
     * is merged with the previous order in the sequence. The first order serves as the
     * initial target for subsequent merges.
     *
     * Example payload:
     * {
     *    "orders": [5005, 4598],  // Orders to be processed
     *    "firstOrder": 5208       // Initial target order
     * }
     *
     * Process flow:
     * 1. First order (5208) is already processed
     * 2. Order 5005 is merged with 5208
     * 3. Order 4598 is merged with 5005
     * 
     * @param \Illuminate\Http\Request $request Contains the orders to be processed
     * @param int $firstOrder ID of the first order that serves as initial target
     * @return array Status and results of the processing operation
     */
    public function processDispatchedOrders($request, $firstOrder)
    {
        try {
            $results = [];
            $currentTargetId = $firstOrder;
            $currentIndex = 1;

            // Process each remaining order in sequence
            foreach ($request->orders as $orderId) {
                $newOrder = Order::with('disp')->find($orderId);
                $targetOrder = Order::with('disp')->find($currentTargetId);

                if (!$newOrder || !$targetOrder) {
                    return ['status' => 'failed', 'message' => 'Order not found'];
                }

                // Merge the current order with the target order
                $result = $this->mergeOrders(
                    $currentIndex,
                    $newOrder,
                    $currentTargetId,
                    $targetOrder,
                    $firstOrder,
                    $orderId
                );

                $results[] = $result;

                // Update the target for the next iteration
                $currentTargetId = $orderId;
                $currentIndex++;
            }

            return [
                'status' => 'success',
                'message' => 'All orders processed successfully',
                'results' => $results
            ];
        } catch (\Exception $e) {
            \Log::error('Error in processDispatchedOrders: ' . $e->getMessage(), [
                'first_order' => $firstOrder,
                'remaining_orders' => $request->orders,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 'failed',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Merge orders based on their current state and position
     * 
     * This method determines the appropriate operation to perform based on the current
     * state of both orders and their relationship, then executes that operation.
     *
     * Operation determination factors:
     * - Current index in the sequence
     * - Whether orders are already dispatched
     * - Parent-child relationships
     * - Vehicle assignments
     *
     * @param int $currentIndex Position in the sequence
     * @param Order $newOrder Order being merged
     * @param int $currentTargetId ID of the target order
     * @param Order $targetOrder Target order instance
     * @param int $order_A Original parent order ID
     * @param int $orderId ID of the order being merged
     * @return array Result of the merge operation
     */
    public function mergeOrders($currentIndex, $newOrder, $currentTargetId, $targetOrder, $order_A, $orderId)
    {
        $operation = $this->determineOperation($currentIndex, $newOrder, $currentTargetId, $targetOrder, $order_A, $orderId);

        if (empty($operation) || $operation == 'skipped') {
            $result = ['orderId' => $orderId, 'status' => 'failed', 'message' => 'No operation specified to be performed'];
            return $result;
        }

        $result = $this->executeOperation($operation, $orderId, $currentTargetId, $order_A, $currentIndex);
        return $result;
    }

    /**
     * Execute the determined merge operation
     * 
     * This method handles the execution of various merge operations based on the
     * determined operation type. Each operation type represents a different scenario
     * for merging orders.
     *
     * Available Operations:
     * - make_new_parent_order: Creates a new parent order
     * - make_new_child_order: Adds as a child to existing parent
     * - make_parent_to_child_within_same_runs: Converts parent to child in same run
     * - make_child_to_parent_within_same_runs: Promotes child to parent in same run
     * - make_child_to_child_within_same_runs: Moves child order within same run
     * - make_parent_to_parent_between_different_runs: Merges parents from different runs
     * - make_parent_to_child_between_different_runs: Moves parent to child between runs
     * - make_child_to_parent_between_different_runs: Promotes child to parent between runs
     * - make_child_to_child_between_different_runs: Moves child between different runs
     *
     * @param string $operation Type of operation to perform
     * @param int $orderId ID of the order being processed
     * @param int $currentTargetId ID of the target order
     * @param int $order_A Original parent order ID
     * @param int $currentIndex Position in the sequence
     * @return array Result of the operation
     */
    public function executeOperation($operation, $orderId, $currentTargetId, $order_A, $currentIndex)
    {
        switch ($operation) {
            case 'make_new_parent_order':
                $operationResult = $this->makeNewParentOrder($currentTargetId, $orderId);
                break;

            case 'make_new_child_order':
                $operationResult = $this->makeNewChildOrder($order_A, $orderId, $currentIndex);
                break;

            case 'make_parent_to_child_within_same_runs':
                $operationResult = $this->makeParentToChildWithSameRuns($orderId, $currentIndex, $currentTargetId, $order_A);
                break;

            case 'make_child_to_parent_within_same_runs':
                $operationResult = $this->makeChildToParentWithSameRuns($currentTargetId, $orderId);
                break;

            case 'make_child_to_child_within_same_runs':
                $operationResult = $this->makeChildToChildWithSameRuns($currentTargetId, $orderId, $currentIndex, $order_A);
                break;

            case 'make_parent_to_parent_between_different_runs':
                $operationResult = $this->makeParentToParentBetweenDifferentRuns($currentTargetId, $orderId);
                break;

            case 'make_parent_to_child_between_different_runs':
                $operationResult = $this->makeParentToChildBetweenDifferentRuns($orderId, $currentTargetId, $currentIndex, $order_A);
                break;

            case 'make_child_to_parent_between_different_runs':
                $operationResult = $this->makeChildToParentBetweenDifferentRuns($orderId, $currentTargetId);
                break;

            case 'make_child_to_child_between_different_runs':
                $operationResult = $this->makeChildToChildBetweenDifferentRuns($orderId, $order_A, $currentIndex);
                break;

            default:
                $operationResult = ['status' => 'failed', 'message' => 'Invalid operation'];
        }

        return array_merge(
            ['orderId' => $orderId, 'operation' => $operation],
            $operationResult ?? ['status' => 'success', 'message' => 'Operation completed']
        );
    }

    /**
     * Determine the appropriate merge operation
     * 
     * This method analyzes the current state of both orders and their relationships
     * to determine the most appropriate merge operation to perform.
     *
     * Factors considered:
     * - Current index in sequence
     * - Dispatch status of orders
     * - Parent-child relationships
     * - Vehicle assignments
     * - Run assignments
     *
     * @param int $currentIndex Position in sequence
     * @param Order $newOrder Order being merged
     * @param int $currentTargetId Target order ID
     * @param Order $targetOrder Target order instance
     * @param int $order_A Original parent order ID
     * @param int $orderId ID of order being merged
     * @return string Operation to be performed
     */
    public function determineOperation($currentIndex, $newOrder, $currentTargetId, $targetOrder, $order_A, $orderId)
    {
        // Determine operation - using original variable names
        $operation = '';
        if ($currentIndex == 0) {
            if ($newOrder->disp == null) {
                $operation = 'make_new_parent_order';
            } else if ($currentTargetId == $newOrder->max_parent_order_id) {
                $operation = 'make_child_to_parent_within_same_runs';
            } else if ($targetOrder->max_parent_order_id == null && $newOrder->max_parent_order_id == null) {
                $operation = 'make_parent_to_parent_between_different_runs';
            } else if ($currentTargetId != $newOrder->max_parent_order_id) {
                $operation = 'make_child_to_parent_between_different_runs';
            }
        } else {
            if ($newOrder->disp == null) {
                $operation = 'make_new_child_order';
            } else if ($orderId == $targetOrder->max_parent_order_id) {
                $operation = 'make_parent_to_child_within_same_runs';
            } else if (
                $orderId == $currentTargetId &&
                $currentTargetId == $order_A &&
                $currentIndex != 0
            ) {
                $operation = 'make_parent_to_child_within_same_runs';
            } else if (
                $targetOrder->max_parent_order_id != null &&
                $newOrder->max_parent_order_id != null &&
                $targetOrder->max_parent_order_id == $newOrder->max_parent_order_id
            ) {
                $operation = 'make_child_to_child_within_same_runs';
            } else if (
                $targetOrder->max_parent_order_id != $orderId &&
                $newOrder->max_parent_order_id == null
            ) {
                $operation = 'make_parent_to_child_between_different_runs';
            } else if (
                $targetOrder->max_parent_order_id != $newOrder->max_parent_order_id &&
                $newOrder->max_parent_order_id != null &&
                $targetOrder->max_parent_order_id != null
            ) {
                $operation = 'make_child_to_child_between_different_runs';
            } else if (
                $currentTargetId == $order_A &&
                $newOrder->max_parent_order_id != null &&
                $targetOrder->max_parent_order_id == null
            ) {
                $operation = 'make_child_to_child_between_different_runs';
            }
        }

        if (empty($operation)) {
            $operation = 'skipped';
        }

        return $operation;
    }

    /**
     * Create a new parent order from an existing order
     * 
     * This operation converts an existing order into a parent order and updates
     * all related orders and dispatch entries accordingly.
     *
     * Process:
     * 1. Load orders with relationships
     * 2. Update dispatch entries
     * 3. Update parent-child relationships
     * 4. Recalculate distances
     *
     * @param int $targetOrderId The order to convert to parent
     * @param int $newOrderId The new order ID
     * @return array Operation result status and message
     */
    public function makeNewParentOrder($targetOrderId, $newOrderId)
    {
        try {
            // Load orders with necessary relationships
            $previousParentOrder = Order::with(['childOrders' => function ($query) {
                $query->join('disp', 'id', '=', 'disp.oid')
                    ->orderBy('disp.num', 'ASC');
            }])->find($targetOrderId);

            $newParentOrder = Order::find($newOrderId);

            if (!$previousParentOrder || !$newParentOrder) {
                return ['status' => 'failed', 'message' => 'Order not found'];
            }

            // Validate if dispatch exists
            if (!$previousParentOrder->disp) {
                return ['status' => 'failed', 'message' => 'Dispatch not found for order'];
            }

            // 2 means send message
            // 0 means locked
            // 1 means not locked
            $lockedValue = $previousParentOrder->locked == 2 ? 0 : ($previousParentOrder->locked == 0 ? 0 : 1);

            // new dispatch for new Parent Order
            DB::table('disp')->insert([
                'van' => $previousParentOrder->disp->van,
                'id_route' => $previousParentOrder->disp->id_route,
                'oid' => $newParentOrder->id,
                'num' => 0,
                'date' => Carbon::now()->format('d-M-Y'),
            ]);

            // Update new parent order
            $newParentOrder->update([
                'max_parent_order_id' => null,
                'orderStatus' => 'Routed',
                'locked' => $lockedValue
            ]);

            // Update previous parent order to first child order
            $previousParentOrder->disp->update(['num' => 1]);
            $previousParentOrder->update([
                'max_parent_order_id' => $newParentOrder->id
            ]);

            // Update num values in disp table for child orders of previous parent order
            DB::table('orders')
                ->join('disp', 'orders.id', '=', 'disp.oid')
                ->where('max_parent_order_id', $targetOrderId)
                ->orderBy('disp.num', 'ASC')
                ->increment('disp.num');

            // Update all child orders
            DB::table('orders')
                ->where('max_parent_order_id', $targetOrderId)
                ->update([
                    'max_parent_order_id' => $newOrderId,
                    'locked' => $lockedValue
                ]);

            // Recalculate distances and times
            $distanceHelper = new MapsHelper();
            $distanceHelper->calculateDistanceAndTime($newOrderId);

            return ['status' => 'success', 'message' => 'Merge completed successfully'];
        } catch (\Exception $e) {
            Log::error('Error in makeNewParentOrder: ' . $e->getMessage(), [
                'targetOrderId' => $targetOrderId,
                'newOrderId' => $newOrderId,
                'trace' => $e->getTraceAsString()
            ]);
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * Add a new child order to an existing parent
     * 
     * This operation creates a new child order relationship and updates
     * the dispatch sequence accordingly.
     *
     * Process:
     * 1. Validate parent and child orders
     * 2. Create dispatch entry for child
     * 3. Update order relationships
     * 4. Resequence existing child orders
     * 5. Recalculate distances
     *
     * @param int $parentOrderId The parent order ID
     * @param int $newOrderId The order to make child
     * @param int $position Position in the sequence
     * @return array Operation result status and message
     */
    public function makeNewChildOrder($parentOrderId, $newOrderId, $position)
    {
        try {
            // Load parent order with dispatch relationship
            $parentOrder = Order::with('disp')->find($parentOrderId);
            $childOrder = Order::find($newOrderId);

            if (!$parentOrder || !$childOrder) {
                return ['status' => 'failed', 'message' => 'Order not found'];
            }

            // Validate if dispatch exists
            if (!$parentOrder->disp) {
                return ['status' => 'failed', 'message' => 'Dispatch not found for parent order'];
            }

            // Set locked value based on parent order
            $lockedValue = $parentOrder->locked == 2 ? 0 : ($parentOrder->locked == 0 ? 0 : 1);

            // Create new dispatch for child order
            $disp = DB::table('disp')->insert([
                'van' => $parentOrder->disp->van,
                'oid' => $newOrderId,
                'num' => $position,
                'id_route' => $parentOrder->disp->id_route,
                'date' => Carbon::now()->format('d-M-Y'),
            ]);

            // Update child order
            DB::table('orders')->where('id', $newOrderId)->update([
                'max_parent_order_id' => $parentOrderId,
                'orderStatus' => 'Routed',
                'locked' => $lockedValue
            ]);

            // First check if there's an existing order at the exact position
            $minPosition = DB::table('orders')
                ->join('disp', 'orders.id', '=', 'disp.oid')
                ->where('orders.max_parent_order_id', $parentOrderId)
                ->where('disp.num', '>=', $position)
                ->min('disp.num');

            // Only increment if there's a collision at the exact position
            if ($minPosition == $position) {
                DB::table('orders')
                    ->join('disp', 'orders.id', '=', 'disp.oid')
                    ->where('orders.id', '!=', $newOrderId)
                    ->where('orders.max_parent_order_id', $parentOrderId)
                    ->where('disp.num', '>=', $position)
                    ->orderBy('disp.num', 'ASC')
                    ->increment('disp.num');
            }

            // Recalculate distances and times
            $distanceHelper = new MapsHelper();
            $distanceHelper->calculateDistanceAndTime($parentOrderId);

            return ['status' => 'success', 'message' => 'Merge completed successfully'];
        } catch (\Exception $e) {
            Log::error('Error in makeNewChildOrder: ' . $e->getMessage(), [
                'parentOrderId' => $parentOrderId,
                'newOrderId' => $newOrderId,
                'position' => $position,
                'trace' => $e->getTraceAsString()
            ]);
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * Convert a parent order to a child order within the same run
     * 
     * This operation changes a parent order into a child order while
     * maintaining all relationships within the same delivery run.
     *
     * Process:
     * 1. Update parent order status
     * 2. Reassign child orders
     * 3. Update dispatch sequence
     * 4. Recalculate distances
     *
     * @param int $newOrderId The order to convert
     * @param int $position New position in sequence
     * @param int $targetOrderId Target parent order
     * @param int $parentOrderId Original parent order
     * @return array Operation result status and message
     */
    public function makeParentToChildWithSameRuns($newOrderId, $position, $targetOrderId, $parentOrderId)
    {
        try {
            // Load parent order with child orders relationship
            $parentOrder = Order::with(['childOrders' => function ($query) {
                $query->join('disp', 'id', '=', 'disp.oid')
                    ->orderBy('disp.num', 'ASC');
            }])->find($parentOrderId);

            if (!$parentOrder) {
                return ['status' => 'failed', 'message' => 'Order not found'];
            }

            // Get first child order
            $firstChildOrder = $parentOrder->childOrders->first();
            if (!$firstChildOrder) {
                return ['status' => 'failed', 'message' => 'No child order found'];
            }

            // Update first child order to become new parent
            $firstChildOrder->update([
                'max_parent_order_id' => null
            ]);

            // Update dispatch numbers
            Disp::where('oid', $firstChildOrder->id)
                ->update(['num' => 0, 'id_route' => $parentOrder->disp->id_route]);

            Disp::where('oid', $parentOrderId)
                ->update(['num' => $position]);

            // Update positions of other child orders
            DB::table('orders')
                ->join('disp', 'orders.id', '=', 'disp.oid')
                ->where('orders.id', '!=', $firstChildOrder->id)
                ->where('orders.max_parent_order_id', $parentOrderId)
                ->where('disp.num', '>=', $position)
                ->orderBy('disp.num', 'ASC')
                ->increment('disp.num');

            // Update parent order and its children
            $parentOrder->update([
                'max_parent_order_id' => $firstChildOrder->id
            ]);

            // Update all child orders to point to new parent
            Order::where('max_parent_order_id', $parentOrderId)
                ->update(['max_parent_order_id' => $firstChildOrder->id]);

            // Recalculate distances and times
            $distanceHelper = new MapsHelper();
            $distanceHelper->calculateDistanceAndTime($firstChildOrder->id);

            return ['status' => 'success', 'message' => 'Merge completed successfully'];
        } catch (\Exception $e) {
            Log::error('Error in makeParentToChildWithSameRuns: ' . $e->getMessage(), [
                'newOrderId' => $newOrderId,
                'position' => $position,
                'targetOrderId' => $targetOrderId,
                'parentOrderId' => $parentOrderId,
                'trace' => $e->getTraceAsString()
            ]);
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * Convert a child order to a parent order within the same run
     * 
     * This operation promotes a child order to parent status while
     * maintaining all relationships within the same delivery run.
     *
     * Process:
     * 1. Update child order status
     * 2. Reassign other child orders
     * 3. Update dispatch sequence
     * 4. Recalculate distances
     *
     * @param int $targetOrderId Current parent order
     * @param int $newOrderId Order to promote to parent
     * @return array Operation result status and message
     */
    public function makeChildToParentWithSameRuns($targetOrderId, $newOrderId)
    {
        try {
            // Load orders with necessary relationships
            $parentOrder = Order::with(['childOrders' => function ($query) {
                $query->join('disp', 'id', '=', 'disp.oid')
                    ->orderBy('disp.num', 'ASC');
            }])->find($targetOrderId);

            $childOrder = Order::find($newOrderId);

            if (!$parentOrder || !$childOrder) {
                return ['status' => 'failed', 'message' => 'Order not found'];
            }

            // Validate if dispatch exists
            if (!$parentOrder->disp) {
                return ['status' => 'failed', 'message' => 'Dispatch not found for order'];
            }

            // Update child order to become parent
            $childOrder->update([
                'max_parent_order_id' => null
            ]);

            // Update dispatch numbers for new parent
            Disp::where('oid', $newOrderId)
                ->update(['num' => 0]);

            // Update num values for all child orders
            DB::table('orders')
                ->join('disp', 'orders.id', '=', 'disp.oid')
                ->where('max_parent_order_id', $targetOrderId)
                ->orderBy('disp.num', 'ASC')
                ->increment('disp.num');

            // Update previous parent to become first child
            $parentOrder->update([
                'max_parent_order_id' => $newOrderId
            ]);

            // Set previous parent as first child
            Disp::where('oid', $targetOrderId)
                ->update(['num' => 1]);

            // Update all child orders to point to new parent
            Order::where('max_parent_order_id', $targetOrderId)
                ->update(['max_parent_order_id' => $newOrderId]);

            // Recalculate distances and times
            $distanceHelper = new MapsHelper();
            $distanceHelper->calculateDistanceAndTime($newOrderId);

            return ['status' => 'success', 'message' => 'Merge completed successfully'];
        } catch (\Exception $e) {
            Log::error('Error in makeChildToParentWithSameRuns: ' . $e->getMessage(), [
                'targetOrderId' => $targetOrderId,
                'newOrderId' => $newOrderId,
                'trace' => $e->getTraceAsString()
            ]);
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * Move a child order to another position within the same run
     * 
     * This operation changes the sequence position of a child order
     * while maintaining all relationships within the same delivery run.
     *
     * Process:
     * 1. Update dispatch sequence
     * 2. Maintain parent-child relationships
     * 3. Recalculate distances
     *
     * @param int $targetOrderId Target order ID
     * @param int $newOrderId Order to move
     * @param int $position New position in sequence
     * @param int $parentOrderId Parent order ID
     * @return array Operation result status and message
     */
    public function makeChildToChildWithSameRuns($targetOrderId, $newOrderId, $position, $parentOrderId)
    {
        try {
            // Load orders with necessary relationships
            $parentOrder = Order::with('disp')->find($targetOrderId);
            $childOrder = Order::with('disp')->find($newOrderId);

            if (!$parentOrder || !$childOrder) {
                return ['status' => 'failed', 'message' => 'Order not found'];
            }

            // Validate if dispatches exist
            if (!$parentOrder->disp || !$childOrder->disp) {
                return ['status' => 'failed', 'message' => 'Dispatch not found for orders'];
            }

            // Update positions of existing child orders
            DB::table('orders')
                ->join('disp', 'orders.id', '=', 'disp.oid')
                ->where('max_parent_order_id', $parentOrderId)
                ->where('disp.num', '>=', $position)
                ->orderBy('disp.num', 'ASC')
                ->increment('disp.num');

            // Update position of the moved child order
            Disp::where('oid', $newOrderId)
                ->update([
                    'num' => $position
                ]);

            // Update child order's parent reference if needed
            $childOrder->update([
                'max_parent_order_id' => $parentOrderId
            ]);

            // Recalculate distances and times
            $distanceHelper = new MapsHelper();
            $distanceHelper->calculateDistanceAndTime($parentOrderId);

            return ['status' => 'success', 'message' => 'Merge completed successfully'];
        } catch (\Exception $e) {
            Log::error('Error in makeChildToChildWithSameRuns: ' . $e->getMessage(), [
                'targetOrderId' => $targetOrderId,
                'newOrderId' => $newOrderId,
                'position' => $position,
                'parentOrderId' => $parentOrderId,
                'trace' => $e->getTraceAsString()
            ]);
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * Merge parent orders from different runs
     * 
     * This operation combines two parent orders from different delivery runs,
     * maintaining all child relationships and updating sequences accordingly.
     *
     * Process:
     * 1. Handle first child order if exists
     * 2. Update dispatch details
     * 3. Reassign child orders
     * 4. Recalculate distances
     *
     * @param int $targetOrderId First parent order
     * @param int $newOrderId Second parent order
     * @return array Operation result status and message
     */
    public function makeParentToParentBetweenDifferentRuns($targetOrderId, $newOrderId)
    {
        try {
            // Load orders with necessary relationships
            $newOrder = Order::with(['childOrders' => function ($query) {
                $query->join('disp', 'id', '=', 'disp.oid')
                    ->orderBy('disp.num', 'ASC');
            }])->find($newOrderId);

            $targetOrder = Order::with('disp')->find($targetOrderId);

            if (!$targetOrder || !$newOrder) {
                return ['status' => 'failed', 'message' => 'Order not found'];
            }

            // Validate if dispatch exists
            if (!$targetOrder->disp) {
                return ['status' => 'failed', 'message' => 'Dispatch not found for target order'];
            }

            // Update new order locked status
            $lockedValue = $targetOrder->locked == 2 ? 0 : ($targetOrder->locked == 0 ? 0 : 1);

            $newOrder->update(['locked' => $lockedValue]);

            // Handle first child order if exists
            $firstChildOrder = $newOrder->childOrders->first();
            if ($firstChildOrder) {
                // Update first child order to become new parent
                $firstChildOrder->update([
                    'max_parent_order_id' => null
                ]);

                // Update dispatch number for first child
                Disp::where('oid', $firstChildOrder->id)
                    ->update(['num' => 0]);

                // Update parent reference for all child orders
                Order::where('max_parent_order_id', $newOrderId)
                    ->update(['max_parent_order_id' => $firstChildOrder->id]);

                // Recalculate distances for first child
                $distanceHelper = new MapsHelper();
                $distanceHelper->calculateDistanceAndTime($firstChildOrder->id);
            }

            // Update positions of existing child orders
            DB::table('orders')
                ->join('disp', 'orders.id', '=', 'disp.oid')
                ->where('max_parent_order_id', $targetOrderId)
                ->orderBy('disp.num', 'ASC')
                ->increment('disp.num');

            // Update dispatch details
            Disp::where('oid', $targetOrderId)->update(['num' => 1]);
            Disp::where('oid', $newOrderId)->update(['van' => $targetOrder->disp->van, 'id_route' => $targetOrder->disp->id_route]);

            // Update target order and its children
            $targetOrder->update(['max_parent_order_id' => $newOrderId]);

            Order::where('max_parent_order_id', $targetOrderId)
                ->update(['max_parent_order_id' => $newOrderId]);

            // Recalculate distances for new parent order
            $distanceHelper = new MapsHelper();
            $distanceHelper->calculateDistanceAndTime($newOrderId);

            return ['status' => 'success', 'message' => 'Merge completed successfully'];
        } catch (\Exception $e) {
            Log::error('Error in makeParentToParentBetweenDifferentRuns: ' . $e->getMessage(), [
                'targetOrderId' => $targetOrderId,
                'newOrderId' => $newOrderId,
                'trace' => $e->getTraceAsString()
            ]);
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * Convert a parent order to a child order between different runs
     * 
     * This operation changes a parent order into a child order while
     * moving it between different delivery runs.
     *
     * Process:
     * 1. Handle existing child orders
     * 2. Update dispatch details
     * 3. Update parent-child relationships
     * 4. Recalculate distances
     *
     * @param int $newOrderId Order to convert
     * @param int $targetOrderId Target parent order
     * @param int $position New position in sequence
     * @param int $existingParentOrder Current parent order
     * @return array Operation result status and message
     */
    public function makeParentToChildBetweenDifferentRuns($newOrderId, $targetOrderId, $position, $existingParentOrder)
    {
        try {
            // Load orders with necessary relationships
            $parentOrder = Order::with(['childOrders' => function ($query) {
                $query->join('disp', 'id', '=', 'disp.oid')
                    ->orderBy('disp.num', 'ASC');
            }])->find($newOrderId);

            $targetOrder = Order::with('disp')->find($targetOrderId);

            if (!$parentOrder || !$targetOrder) {
                return ['status' => 'failed', 'message' => 'Order not found'];
            }

            // Validate if dispatch exists
            if (!$targetOrder->disp) {
                return ['status' => 'failed', 'message' => 'Dispatch not found for target order'];
            }

            // Set locked value based on target order
            $lockedValue = $targetOrder->locked == 2 ? 0 : ($targetOrder->locked == 0 ? 0 : 1);


            // Handle first child order if exists
            $firstChildOrder = $parentOrder->childOrders->first();
            if ($firstChildOrder) {
                // Update first child order to become new parent
                $firstChildOrder->update([
                    'max_parent_order_id' => null
                ]);

                // Update dispatch number for first child
                Disp::where('oid', $firstChildOrder->id)
                    ->update(['num' => 0]);

                // Update parent reference for all child orders
                Order::where('max_parent_order_id', $newOrderId)
                    ->update(['max_parent_order_id' => $firstChildOrder->id]);

                // Recalculate distances for first child
                $distanceHelper = new MapsHelper();
                $distanceHelper->calculateDistanceAndTime($firstChildOrder->id);
            }

            // Update positions of existing child orders
            DB::table('orders')
                ->join('disp', 'orders.id', '=', 'disp.oid')
                ->where('max_parent_order_id', $targetOrderId)
                ->where('disp.num', '>=', $position)
                ->orderBy('disp.num', 'ASC')
                ->increment('disp.num');

            // Update dispatch details for the parent order
            Disp::where('oid', $newOrderId)
                ->update([
                    'num' => $position,
                    'van' => $targetOrder->disp->van,
                    'id_route' => $targetOrder->disp->id_route
                ]);

            // Update parent order details
            $parentOrder->update([
                'max_parent_order_id' => $targetOrderId,
                'locked' => $lockedValue
            ]);

            // Recalculate distances for existing parent order
            $distanceHelper = new MapsHelper();
            $distanceHelper->calculateDistanceAndTime($existingParentOrder);

            return ['status' => 'success', 'message' => 'Merge completed successfully'];
        } catch (\Exception $e) {
            Log::error('Error in makeParentToChildBetweenDifferentRuns: ' . $e->getMessage(), [
                'newOrderId' => $newOrderId,
                'targetOrderId' => $targetOrderId,
                'position' => $position,
                'existingParentOrder' => $existingParentOrder,
                'trace' => $e->getTraceAsString()
            ]);
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * Convert a child order to a parent order between different runs
     * 
     * This operation promotes a child order to parent status while
     * moving it between different delivery runs.
     *
     * Process:
     * 1. Update order relationships
     * 2. Update dispatch details
     * 3. Reassign child orders
     * 4. Recalculate distances
     *
     * @param int $childOrderId Order to promote
     * @param int $targetOrderId Target order
     * @return array Operation result status and message
     */
    public function makeChildToParentBetweenDifferentRuns($childOrderId, $targetOrderId)
    {
        try {
            // Load orders with necessary relationships
            $targetOrder = Order::with(['disp', 'childOrders' => function ($query) {
                $query->join('disp', 'id', '=', 'disp.oid')
                    ->orderBy('disp.num', 'ASC');
            }])->find($targetOrderId);

            $childOrder = Order::find($childOrderId);

            if (!$targetOrder || !$childOrder) {
                return ['status' => 'failed', 'message' => 'Order not found'];
            }

            // Validate if dispatch exists
            if (!$targetOrder->disp) {
                return ['status' => 'failed', 'message' => 'Dispatch not found for target order'];
            }

            // Store parent ID for recalculation
            $parentIdOfGivenChildOrder = $childOrder->max_parent_order_id;

            // Update child order to become parent
            $lockedValue = $targetOrder->locked == 2 ? 0 : ($targetOrder->locked == 0 ? 0 : 1);

            $childOrder->update([
                'max_parent_order_id' => null,
                'locked' => $lockedValue
            ]);

            // Update dispatch for child order
            Disp::where('oid', $childOrderId)
                ->update([
                    'num' => 0,
                    'van' => $targetOrder->disp->van,
                    'id_route' => $targetOrder->disp->id_route
                ]);

            // Recalculate distances for previous parent
            $distanceHelper = new MapsHelper();
            $distanceHelper->calculateDistanceAndTime($parentIdOfGivenChildOrder);

            // Update positions of existing child orders
            DB::table('orders')
                ->join('disp', 'orders.id', '=', 'disp.oid')
                ->where('max_parent_order_id', $targetOrderId)
                ->orderBy('disp.num', 'ASC')
                ->increment('disp.num');

            // Update target order dispatch number
            Disp::where('oid', $targetOrderId)
                ->update(['num' => 1]);

            // Update target order and its children
            $targetOrder->update([
                'max_parent_order_id' => $childOrderId
            ]);

            Order::where('max_parent_order_id', $targetOrderId)
                ->update(['max_parent_order_id' => $childOrderId]);

            // Recalculate distances for new parent order
            $distanceHelper->calculateDistanceAndTime($childOrderId);

            return ['status' => 'success', 'message' => 'Merge completed successfully'];
        } catch (\Exception $e) {
            Log::error('Error in makeChildToParentBetweenDifferentRuns: ' . $e->getMessage(), [
                'childOrderId' => $childOrderId,
                'targetOrderId' => $targetOrderId,
                'trace' => $e->getTraceAsString()
            ]);
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * Move a child order between different runs
     * 
     * This operation moves a child order from one delivery run to another,
     * updating all relationships and sequences accordingly.
     *
     * Process:
     * 1. Update dispatch details
     * 2. Update parent-child relationships
     * 3. Recalculate distances for both runs
     *
     * @param int $newOrderId Order to move
     * @param int $parentOrderId New parent order
     * @param int $position New position in sequence
     * @return array Operation result status and message
     */
    public function makeChildToChildBetweenDifferentRuns($newOrderId, $parentOrderId, $position)
    {
        try {
            // Load orders with necessary relationships
            $parentOrder = Order::with('disp')->find($parentOrderId);
            $childOrder = Order::with('disp')->find($newOrderId);

            if (!$parentOrder || !$childOrder) {
                return ['status' => 'failed', 'message' => 'Order not found'];
            }

            // Validate if dispatches exist
            if (!$parentOrder->disp) {
                return ['status' => 'failed', 'message' => 'Dispatch not found for parent order'];
            }

            // Store previous parent ID for recalculation
            $previousParentOrderId = $childOrder->max_parent_order_id;

            // Set locked value based on parent order
            $lockedValue = $parentOrder->locked == 2 ? 0 : ($parentOrder->locked == 0 ? 0 : 1);


            // Update positions of existing child orders
            DB::table('orders')
                ->join('disp', 'orders.id', '=', 'disp.oid')
                ->where('max_parent_order_id', $parentOrderId)
                ->where('disp.num', '>=', $position)
                ->orderBy('disp.num', 'ASC')
                ->increment('disp.num');

            // Update child order details
            DB::table('orders')
                ->where('id', $newOrderId)
                ->update([
                    'max_parent_order_id' => $parentOrderId,
                    'locked' => $lockedValue
                ]);

            // Update dispatch details
            Disp::where('oid', $newOrderId)
                ->update([
                    'num' => $position,
                    'van' => $parentOrder->disp->van,
                    'id_route' => $parentOrder->disp->id_route
                ]);

            // Recalculate distances for both orders
            $distanceHelper = new MapsHelper();
            if ($previousParentOrderId) {
                $distanceHelper->calculateDistanceAndTime($previousParentOrderId);
            }
            $distanceHelper->calculateDistanceAndTime($parentOrderId);

            return ['status' => 'success', 'message' => 'Merge completed successfully'];
        } catch (\Exception $e) {
            Log::error('Error in makeChildToChildBetweenDifferentRuns: ' . $e->getMessage(), [
                'newOrderId' => $newOrderId,
                'parentOrderId' => $parentOrderId,
                'position' => $position,
                'trace' => $e->getTraceAsString()
            ]);
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }
}
