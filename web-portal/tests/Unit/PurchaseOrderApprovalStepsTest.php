<?php

namespace Tests\Unit;

use App\Support\PurchaseOrderApprovalSteps;
use PHPUnit\Framework\TestCase;

class PurchaseOrderApprovalStepsTest extends TestCase
{
    public function test_department_head_can_approve_pending_dept_head_step(): void
    {
        $po = ['status' => 'pending_dept_head', 'approval_tier' => 2];

        $this->assertTrue(
            PurchaseOrderApprovalSteps::userCanApproveCurrentStep($po, ['department_head']),
        );
    }

    public function test_inventory_manager_cannot_approve_pending_dept_head_step(): void
    {
        $po = ['status' => 'pending_dept_head', 'approval_tier' => 2];

        $this->assertFalse(
            PurchaseOrderApprovalSteps::userCanApproveCurrentStep($po, ['inventory_manager']),
        );
    }

    public function test_finance_manager_can_only_approve_finance_step(): void
    {
        $po = ['status' => 'pending_finance', 'approval_tier' => 2];

        $this->assertTrue(
            PurchaseOrderApprovalSteps::userCanApproveCurrentStep($po, ['finance_manager']),
        );
        $this->assertFalse(
            PurchaseOrderApprovalSteps::userCanApproveCurrentStep(
                ['status' => 'pending_dept_head', 'approval_tier' => 2],
                ['finance_manager'],
            ),
        );
    }
}
