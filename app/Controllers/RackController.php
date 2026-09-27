<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Helpers\RackManager;
use App\Helpers\Session;
use App\Helpers\AuditLogger;
use Exception;

class RackController extends BaseController {

    public function createRack(): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        try {
            $rackNumber = intval($_POST['rack_number'] ?? 0);
            $name       = trim($_POST['name'] ?? '');
            $totalSlots = intval($_POST['total_slots'] ?? 15);
            $location   = trim($_POST['location'] ?? 'Main Vault');
            $description= trim($_POST['description'] ?? '');
            $status     = $_POST['status'] ?? 'Active';

            $newRackId = RackManager::createRack([
                'rack_number' => $rackNumber,
                'name'        => $name,
                'total_slots' => $totalSlots,
                'location'    => $location,
                'description' => $description,
                'status'      => $status
            ]);

            AuditLogger::log('Rack Created', Session::get('admin_id'), null, [
                'rack_id'     => $newRackId,
                'name'        => $name,
                'total_slots' => $totalSlots
            ], 'Created new physical storage rack with initial slots');

            Session::setFlash('success', "Rack '{$name}' with {$totalSlots} slots created successfully!");
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
        }

        $this->redirect('/collateral/racks');
    }

    public function updateRack(string $id): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        try {
            $rackId = intval($id);
            $name        = trim($_POST['name'] ?? '');
            $location    = trim($_POST['location'] ?? 'Main Vault');
            $description = trim($_POST['description'] ?? '');
            $status      = $_POST['status'] ?? 'Active';

            RackManager::updateRack($rackId, [
                'name'        => $name,
                'location'    => $location,
                'description' => $description,
                'status'      => $status
            ]);

            AuditLogger::log('Rack Updated', Session::get('admin_id'), null, [
                'rack_id' => $rackId,
                'name'    => $name
            ], 'Updated rack information');

            Session::setFlash('success', "Rack details updated successfully!");
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
        }

        $this->redirect('/collateral/racks');
    }

    public function deleteRack(string $id): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        try {
            $rackId = intval($id);
            RackManager::deleteRack($rackId);

            AuditLogger::log('Rack Deleted', Session::get('admin_id'), null, [
                'rack_id' => $rackId
            ], 'Deleted empty storage rack');

            Session::setFlash('success', 'Rack deleted successfully.');
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
        }

        $this->redirect('/collateral/racks');
    }

    public function createSlot(string $rackId): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        try {
            $rId = intval($rackId);
            $slotNumber = intval($_POST['slot_number'] ?? 0);
            $slotName   = trim($_POST['slot_name'] ?? '');
            $notes      = trim($_POST['notes'] ?? '');
            $status     = $_POST['status'] ?? 'Available';

            $newSlotId = RackManager::createSlot($rId, [
                'slot_number' => $slotNumber,
                'slot_name'   => $slotName,
                'notes'       => $notes,
                'status'      => $status
            ]);

            AuditLogger::log('Rack Slot Added', Session::get('admin_id'), null, [
                'rack_id' => $rId,
                'slot_id' => $newSlotId
            ], 'Added new slot to rack');

            Session::setFlash('success', 'New rack slot added successfully!');
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
        }

        $this->redirect('/collateral/racks');
    }

    public function updateSlot(string $id): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        try {
            $slotId   = intval($id);
            $slotName = trim($_POST['slot_name'] ?? '');
            $status   = $_POST['status'] ?? 'Available';
            $notes    = trim($_POST['notes'] ?? '');

            RackManager::updateSlot($slotId, [
                'slot_name' => $slotName,
                'status'    => $status,
                'notes'     => $notes
            ]);

            AuditLogger::log('Rack Slot Updated', Session::get('admin_id'), null, [
                'slot_id' => $slotId
            ], 'Updated rack slot details');

            Session::setFlash('success', 'Slot updated successfully.');
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
        }

        $this->redirect('/collateral/racks');
    }

    public function deleteSlot(string $id): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        try {
            $slotId = intval($id);
            RackManager::deleteSlot($slotId);

            AuditLogger::log('Rack Slot Deleted', Session::get('admin_id'), null, [
                'slot_id' => $slotId
            ], 'Deleted unoccupied rack slot');

            Session::setFlash('success', 'Slot removed successfully.');
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
        }

        $this->redirect('/collateral/racks');
    }

    public function transferSlot(): void {
        AuthMiddleware::check();
        $this->validateCsrf();

        try {
            $itemId      = intval($_POST['item_id'] ?? 0);
            $newSlotName = trim($_POST['new_slot_name'] ?? '');

            RackManager::transferSlot($itemId, $newSlotName);

            AuditLogger::log('Collateral Slot Transferred', Session::get('admin_id'), null, [
                'item_id'  => $itemId,
                'new_slot' => $newSlotName
            ], 'Transferred collateral item to new slot');

            Session::setFlash('success', "Item transferred to {$newSlotName} successfully!");
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
        }

        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? '/collateral/racks';
        $this->redirect($redirectUrl);
    }
}
