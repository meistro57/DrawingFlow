<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class AgentFlowController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/AgentFlow/Index', [
            'availableNodeTypes' => [
                ['value' => 'trigger', 'label' => 'Trigger'],
                ['value' => 'action', 'label' => 'Action'],
                ['value' => 'condition', 'label' => 'Condition'],
                ['value' => 'delay', 'label' => 'Delay'],
                ['value' => 'notification', 'label' => 'Notification'],
            ],
            'initialNodes' => [
                ['id' => 'n1', 'label' => 'Start', 'type' => 'trigger', 'x' => 80, 'y' => 140],
                ['id' => 'n2', 'label' => 'Validate Input', 'type' => 'condition', 'x' => 360, 'y' => 80],
                ['id' => 'n3', 'label' => 'Create Request', 'type' => 'action', 'x' => 360, 'y' => 260],
                ['id' => 'n4', 'label' => 'Notify Team', 'type' => 'notification', 'x' => 640, 'y' => 170],
            ],
            'initialEdges' => [
                ['id' => 'e1', 'from' => 'n1', 'to' => 'n2'],
                ['id' => 'e2', 'from' => 'n1', 'to' => 'n3'],
                ['id' => 'e3', 'from' => 'n2', 'to' => 'n4'],
                ['id' => 'e4', 'from' => 'n3', 'to' => 'n4'],
            ],
        ]);
    }
}
