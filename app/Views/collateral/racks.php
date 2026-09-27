<?php
$pageTitle = 'Physical Vault Rack & Slot Management System';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$totalCap    = $gridData['total_capacity'];
$occupied    = $gridData['total_occupied'];
$free        = $gridData['total_free'];
$occupancy   = $gridData['occupancy_pct'];
$racks       = $gridData['racks'];
?>

<div class="space-y-6" x-data="{
    activeTab: 'visualizer',
    addRackModal: false,
    editRackModal: false,
    addSlotModal: false,
    editSlotModal: false,
    transferModal: false,
    currentRack: {},
    currentSlot: {},
    transferItem: {}
}">

    <!-- Page Header & Action Bar -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fa-solid fa-cubes text-amber-500"></i> Physical Vault Rack & Slot Management System
            </h1>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">
                Real-time occupancy matrix, slot allocation, and complete CRUD management for vault storage units.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Add New Rack Button -->
            <button @click="addRackModal = true" class="rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 px-3.5 py-2 text-xs font-bold text-white shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-circle-plus"></i>
                <span>Add New Rack</span>
            </button>

            <!-- Auto Assign Pending -->
            <a href="<?= $baseUrl ?>/collateral/auto-assign" class="rounded-xl bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-amber-600 hover:to-yellow-600 px-3.5 py-2 text-xs font-bold text-slate-950 shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                <span>Auto-Assign</span>
                <?php if ($unassignedCount > 0): ?>
                    <span class="rounded-full bg-slate-950 text-amber-400 px-1.5 py-0.5 text-[10px] font-black"><?= $unassignedCount ?></span>
                <?php endif; ?>
            </a>

            <!-- Collateral Directory Link -->
            <a href="<?= $baseUrl ?>/collateral" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition flex items-center gap-1.5">
                <i class="fa-solid fa-list-ul text-slate-500"></i>
                <span>Directory</span>
            </a>
        </div>
    </div>

    <!-- Storage Statistics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Total Vault Capacity -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-extrabold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Vault Capacity</span>
                <i class="fa-solid fa-warehouse text-blue-500 text-sm"></i>
            </div>
            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono"><?= $totalCap ?> Slots</p>
            <span class="text-[11px] text-slate-500 font-bold"><?= count($racks) ?> Physical Racks Configured</span>
        </div>

        <!-- Occupied Slots -->
        <div class="rounded-2xl border border-amber-300 dark:border-amber-500/30 bg-amber-50/60 dark:bg-amber-500/5 p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-extrabold text-amber-800 dark:text-yellow-300 uppercase tracking-wider">Occupied Slots</span>
                <i class="fa-solid fa-box-archive text-amber-600 dark:text-yellow-400 text-sm"></i>
            </div>
            <p class="text-2xl font-black text-amber-700 dark:text-yellow-400 mt-1 font-mono"><?= $occupied ?> / <?= $totalCap ?></p>
            <div class="w-full bg-amber-200/60 dark:bg-slate-700 h-1.5 rounded-full mt-2 overflow-hidden">
                <div class="bg-amber-500 h-full" style="width: <?= min(100, $occupancy) ?>%"></div>
            </div>
            <span class="text-[10px] text-amber-800 dark:text-yellow-400 font-black mt-1 block"><?= $occupancy ?>% Storage Occupancy</span>
        </div>

        <!-- Free Slots -->
        <div class="rounded-2xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50/60 dark:bg-emerald-950/20 p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-extrabold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider">Available Free Slots</span>
                <i class="fa-solid fa-square-check text-emerald-600 dark:text-emerald-400 text-sm"></i>
            </div>
            <p class="text-2xl font-black text-emerald-700 dark:text-emerald-400 mt-1 font-mono"><?= $free ?> Slots</p>
            <span class="text-[11px] text-emerald-800 dark:text-emerald-300 font-bold">Ready for instant assignment</span>
        </div>

        <!-- Unassigned Pending Items -->
        <div class="rounded-2xl border border-purple-300 dark:border-purple-800 bg-purple-50/60 dark:bg-purple-950/20 p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-extrabold text-purple-800 dark:text-purple-300 uppercase tracking-wider">Unassigned Collateral</span>
                <i class="fa-solid fa-circle-exclamation text-purple-600 dark:text-purple-400 text-sm"></i>
            </div>
            <p class="text-2xl font-black text-purple-700 dark:text-purple-400 mt-1 font-mono"><?= $unassignedCount ?> Items</p>
            <?php if ($unassignedCount > 0): ?>
                <a href="<?= $baseUrl ?>/collateral/auto-assign" class="text-[11px] text-purple-700 dark:text-purple-300 font-black underline hover:opacity-80">Auto-assign all items now &rarr;</a>
            <?php else: ?>
                <span class="text-[11px] text-purple-800 dark:text-purple-400 font-bold">All active items allocated</span>
            <?php endif; ?>
        </div>

    </div>

    <!-- Navigation Tabs: Visualizer vs Complete Management Directory -->
    <div class="border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <button @click="activeTab = 'visualizer'" 
                    :class="activeTab === 'visualizer' ? 'border-amber-500 text-amber-600 dark:text-amber-400 font-black border-b-2' : 'text-slate-600 dark:text-slate-400 font-bold hover:text-slate-900 dark:hover:text-white'"
                    class="px-4 py-3 text-xs transition flex items-center gap-2">
                <i class="fa-solid fa-table-cells text-sm"></i>
                <span>Storage Matrix Visualizer</span>
            </button>
            
            <button @click="activeTab = 'manage'" 
                    :class="activeTab === 'manage' ? 'border-amber-500 text-amber-600 dark:text-amber-400 font-black border-b-2' : 'text-slate-600 dark:text-slate-400 font-bold hover:text-slate-900 dark:hover:text-white'"
                    class="px-4 py-3 text-xs transition flex items-center gap-2">
                <i class="fa-solid fa-sliders text-sm"></i>
                <span>Rack & Slot Management Directory</span>
                <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-[10px] font-bold text-slate-700 dark:text-slate-300"><?= count($racks) ?> Racks</span>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 1: VISUALIZER MATRIX VIEW                                             -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'visualizer'" class="space-y-6">

        <!-- Visual Filter Controls & Search -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm">
            
            <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto">
                <button onclick="filterRackGrid('all')" id="btn-filter-all" class="rack-filter-btn active rounded-xl px-3 py-1.5 text-xs font-bold transition bg-amber-500 text-slate-950 shadow-sm">
                    All Slots (<?= $totalCap ?>)
                </button>
                <button onclick="filterRackGrid('occupied')" id="btn-filter-occupied" class="rack-filter-btn rounded-xl px-3 py-1.5 text-xs font-bold transition text-slate-700 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                    Occupied (<?= $occupied ?>)
                </button>
                <button onclick="filterRackGrid('free')" id="btn-filter-free" class="rack-filter-btn rounded-xl px-3 py-1.5 text-xs font-bold transition text-slate-700 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                    Available (<?= $free ?>)
                </button>
                <button onclick="filterRackGrid('GOLD')" id="btn-filter-gold" class="rack-filter-btn rounded-xl px-3 py-1.5 text-xs font-bold transition text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40">
                    Gold Only
                </button>
                <button onclick="filterRackGrid('SILVER')" id="btn-filter-silver" class="rack-filter-btn rounded-xl px-3 py-1.5 text-xs font-bold transition text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">
                    Silver Only
                </button>
            </div>

            <div class="relative w-full md:w-72">
                <input type="text" id="rack-search-input" onkeyup="searchRackGrid()" 
                       placeholder="Search item, loan#, customer, slot..." 
                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 pl-9 pr-3 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-amber-500 focus:outline-none">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-xs text-slate-400"></i>
            </div>

        </div>

        <!-- Dynamic Physical Racks Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5 gap-5">
            
            <?php foreach ($racks as $rack): ?>
                <?php 
                    $rId       = $rack['id'];
                    $rNum      = $rack['rack_number'];
                    $rName     = $rack['name'];
                    $rOccupied = $rack['occupied_count'];
                    $rTotal    = $rack['total_slots'];
                    $rPct      = $rack['occupancy_pct'];
                    $rStatus   = $rack['status'];
                ?>
                <div class="rack-card rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm flex flex-col justify-between" id="rack-card-<?= $rNum ?>">
                    
                    <!-- Rack Card Header -->
                    <div class="border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                                    <i class="fa-solid fa-server text-amber-500"></i> <?= htmlspecialchars($rName) ?>
                                </span>
                                <span class="text-[10px] text-slate-500 font-bold block"><?= htmlspecialchars($rack['location'] ?? 'Main Vault') ?></span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-black text-slate-800 dark:text-slate-200 font-mono">
                                    <?= $rOccupied ?> / <?= $rTotal ?>
                                </span>
                                <span class="text-[10px] font-bold text-slate-400 block font-mono"><?= $rPct ?>% full</span>
                            </div>
                        </div>
                        
                        <!-- Occupancy Progress Bar -->
                        <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full mt-2 overflow-hidden">
                            <div class="h-full <?= $rPct >= 100 ? 'bg-rose-500' : ($rPct >= 70 ? 'bg-amber-500' : 'bg-emerald-500') ?>" style="width: <?= $rPct ?>%"></div>
                        </div>
                    </div>

                    <!-- Slots Grid (5 columns) -->
                    <div class="grid grid-cols-5 gap-2">
                        <?php foreach ($rack['slots'] as $slot): ?>
                            <?php 
                                $sId      = $slot['id'];
                                $sNum     = $slot['slot_number'];
                                $sName    = $slot['slot_name'];
                                $isOcc    = $slot['is_occupied'];
                                $item     = $slot['item'];
                                $itemType = $isOcc ? $item['item_type'] : '';
                                $itemName = $isOcc ? htmlspecialchars($item['item_name']) : '';
                                $loanNum  = $isOcc ? htmlspecialchars($item['loan_number']) : '';
                                $custName = $isOcc ? htmlspecialchars($item['customer_name']) : '';
                                $weight   = $isOcc ? number_format($item['net_weight'], 2) . 'g' : '';

                                // Smart positioning: avoid clipping on edges of the 5-column grid
                                $col = ($sNum - 1) % 5;
                                if ($col === 0) {
                                    $popoverPos = 'left-0';
                                    $caretPos   = 'left-5';
                                } elseif ($col === 4) {
                                    $popoverPos = 'right-0';
                                    $caretPos   = 'right-5';
                                } else {
                                    $popoverPos = 'left-1/2 -translate-x-1/2';
                                    $caretPos   = 'left-1/2 -translate-x-1/2';
                                }
                            ?>
                            
                            <div class="slot-box relative group rounded-xl p-2 text-center transition cursor-pointer border text-xs font-mono font-bold
                                <?= $isOcc 
                                    ? ($itemType === 'GOLD' 
                                        ? 'bg-amber-50/90 dark:bg-yellow-500/10 border-amber-300 dark:border-yellow-500/40 text-amber-800 dark:text-yellow-300 hover:border-amber-500 shadow-xs' 
                                        : 'bg-slate-100 dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 hover:border-slate-400')
                                    : 'bg-emerald-50/70 dark:bg-emerald-950/20 border-emerald-300 dark:border-emerald-900/50 text-emerald-800 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/40' 
                                ?>"
                                data-occupied="<?= $isOcc ? '1' : '0' ?>"
                                data-type="<?= $itemType ?>"
                                data-search-text="<?= strtolower($sName . ' ' . $itemName . ' ' . $loanNum . ' ' . $custName) ?>"
                                onclick="<?= $isOcc ? "viewItemModal(" . json_encode($item) . ")" : "void(0)" ?>"
                            >
                                <!-- Slot Identifier -->
                                <div class="text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400 font-sans">S<?= sprintf('%02d', $sNum) ?></div>
                                
                                <div class="mt-0.5">
                                    <?php if ($isOcc): ?>
                                        <i class="fa-solid <?= $itemType === 'GOLD' ? 'fa-gem text-amber-500' : 'fa-ring text-slate-400' ?> text-xs"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-plus text-emerald-500 dark:text-emerald-400 text-[10px]"></i>
                                    <?php endif; ?>
                                </div>

                                <!-- Proper Popover Tooltip on Hover (Crisp Dual-Theme) -->
                                <div class="slot-popover absolute bottom-full <?= $popoverPos ?> mb-2.5 hidden group-hover:flex flex-col z-50 w-56 rounded-2xl p-3 text-left text-xs pointer-events-none transition-all duration-150">
                                    
                                    <!-- Header: Slot Name & Status/Type Badge -->
                                    <div class="flex items-center justify-between gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-600 dark:text-amber-400 text-[10px]">
                                                <i class="fa-solid fa-layer-group"></i>
                                            </span>
                                            <span class="font-black text-slate-900 dark:text-white text-xs tracking-tight truncate"><?= htmlspecialchars($sName) ?></span>
                                        </div>
                                        <span class="shrink-0 text-[9px] px-2 py-0.5 rounded-md font-black uppercase tracking-wider flex items-center gap-1 
                                            <?= $isOcc 
                                                ? ($itemType === 'GOLD' 
                                                    ? 'bg-amber-100 text-amber-900 dark:bg-amber-500/20 dark:text-amber-300 border border-amber-300/60 dark:border-amber-500/30' 
                                                    : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200 border border-slate-300/80 dark:border-slate-700') 
                                                : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300 border border-emerald-300/60 dark:border-emerald-500/30' ?>">
                                            <?php if ($isOcc): ?>
                                                <i class="fa-solid <?= $itemType === 'GOLD' ? 'fa-gem text-amber-600 dark:text-amber-400' : 'fa-ring text-slate-500 dark:text-slate-400' ?> text-[8px]"></i>
                                                <?= $itemType ?>
                                            <?php else: ?>
                                                <i class="fa-solid fa-check text-emerald-600 dark:text-emerald-400 text-[8px]"></i>
                                                FREE
                                            <?php endif; ?>
                                        </span>
                                    </div>

                                    <!-- Content -->
                                    <?php if ($isOcc): ?>
                                        <div class="py-2 space-y-1.5 text-[11px]">
                                            <div class="font-extrabold text-slate-900 dark:text-white text-xs truncate flex items-center gap-1.5">
                                                <i class="fa-solid fa-tag text-amber-500 text-[10px] shrink-0"></i>
                                                <span class="truncate"><?= $itemName ?></span>
                                            </div>

                                            <div class="flex items-center justify-between gap-2 py-0.5">
                                                <span class="text-slate-500 dark:text-slate-400 font-medium shrink-0">Loan#:</span>
                                                <span class="font-mono font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-1.5 py-0.5 rounded border border-amber-200/60 dark:border-amber-500/20 truncate"><?= $loanNum ?></span>
                                            </div>

                                            <div class="flex items-center justify-between gap-2 py-0.5">
                                                <span class="text-slate-500 dark:text-slate-400 font-medium shrink-0">Cust:</span>
                                                <span class="font-semibold text-slate-800 dark:text-slate-200 truncate max-w-[120px] text-right"><?= $custName ?></span>
                                            </div>

                                            <div class="flex items-center justify-between gap-2 py-0.5">
                                                <span class="text-slate-500 dark:text-slate-400 font-medium shrink-0">Net Wt:</span>
                                                <span class="font-mono font-black text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-1.5 py-0.5 rounded border border-emerald-200/60 dark:border-emerald-500/20"><?= $weight ?></span>
                                            </div>
                                        </div>

                                        <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-center gap-1 text-[10px] text-amber-600 dark:text-amber-400 font-bold italic">
                                            <i class="fa-solid fa-hand-pointer text-[9px]"></i>
                                            <span>Click slot to view & transfer</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="py-2.5 text-center">
                                            <div class="h-6 w-6 rounded-full bg-emerald-100 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto mb-1">
                                                <i class="fa-solid fa-check text-[10px]"></i>
                                            </div>
                                            <p class="font-bold text-slate-800 dark:text-slate-200 text-[11px]">Empty Slot Ready</p>
                                            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Ready for instant assignment</p>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Caret Pointer Arrow -->
                                    <div class="slot-popover-arrow absolute -bottom-1.5 <?= $caretPos ?> w-3 h-3 rotate-45 border-r border-b"></div>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    </div>

                    <div class="mt-3 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px]">
                        <span class="text-slate-500 font-semibold"><?= $rack['available_count'] ?> Free</span>
                        <button @click="currentRack = <?= htmlspecialchars(json_encode($rack)) ?>; editRackModal = true" class="text-blue-600 dark:text-blue-400 font-bold hover:underline">
                            <i class="fa-solid fa-gear mr-1"></i> Manage
                        </button>
                    </div>

                </div>
            <?php endforeach; ?>

        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2: RACK & SLOT MANAGEMENT DIRECTORY (CRUD)                           -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'manage'" class="space-y-6">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-base font-black text-slate-900 dark:text-white">Physical Storage Units & Slot Configurations</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Add, rename, resize capacities, and manage individual rack slots.</p>
            </div>
            <button @click="addRackModal = true" class="rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 px-4 py-2.5 text-xs font-bold text-white shadow transition flex items-center gap-2">
                <i class="fa-solid fa-plus"></i>
                <span>Add Storage Rack</span>
            </button>
        </div>

        <!-- Racks Table Accordion List -->
        <div class="space-y-4">
            <?php foreach ($racks as $r): ?>
                <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden" x-data="{ expanded: false }">
                    
                    <!-- Rack Summary Bar -->
                    <div class="p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/50 dark:bg-slate-950/40">
                        <div class="flex items-center gap-3">
                            <button @click="expanded = !expanded" class="h-8 w-8 rounded-xl bg-slate-200 dark:bg-slate-800 flex items-center justify-center text-slate-700 dark:text-slate-300 hover:bg-amber-500 hover:text-slate-950 transition">
                                <i class="fa-solid fa-chevron-right text-xs transition transform" :class="expanded ? 'rotate-90' : ''"></i>
                            </button>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-black text-slate-900 dark:text-white"><?= htmlspecialchars($r['name']) ?></h3>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-black <?= $r['status'] === 'Active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400' : 'bg-slate-200 text-slate-700' ?>">
                                        <?= $r['status'] ?>
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                                    <i class="fa-solid fa-location-dot text-slate-400 mr-1"></i> <?= htmlspecialchars($r['location'] ?? 'Main Vault') ?>
                                    <?php if (!empty($r['description'])): ?>
                                        &bull; <span class="italic"><?= htmlspecialchars($r['description']) ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>

                        <!-- Metrics & Quick Actions -->
                        <div class="flex items-center gap-3">
                            <div class="text-right font-mono text-xs pr-2 border-r border-slate-200 dark:border-slate-800">
                                <span class="font-extrabold text-slate-900 dark:text-white"><?= $r['occupied_count'] ?> Occupied</span> / 
                                <span class="text-slate-500"><?= $r['total_slots'] ?> Total</span>
                                <span class="block text-[10px] text-amber-600 dark:text-amber-400 font-bold"><?= $r['occupancy_pct'] ?>% Capacity</span>
                            </div>

                            <!-- Add Slot Button -->
                            <button @click="currentRack = <?= htmlspecialchars(json_encode($r)) ?>; addSlotModal = true" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition flex items-center gap-1.5">
                                <i class="fa-solid fa-plus text-emerald-500"></i>
                                <span>Add Slot</span>
                            </button>

                            <!-- Edit Rack Button -->
                            <button @click="currentRack = <?= htmlspecialchars(json_encode($r)) ?>; editRackModal = true" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition flex items-center gap-1.5">
                                <i class="fa-solid fa-pen text-amber-500"></i>
                                <span>Edit</span>
                            </button>

                            <!-- Delete Rack Button -->
                            <?php if ($r['occupied_count'] == 0): ?>
                                <form action="<?= $baseUrl ?>/racks/<?= $r['id'] ?>/delete" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete <?= htmlspecialchars(addslashes($r['name'])) ?>?')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <button type="submit" class="rounded-xl border border-rose-200 dark:border-rose-900/40 bg-rose-50 dark:bg-rose-950/20 px-3 py-1.5 text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-100 transition">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button type="button" disabled title="Cannot delete rack containing occupied items" class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800 px-3 py-1.5 text-xs font-bold text-slate-400 cursor-not-allowed opacity-60">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Expandable Slots Detail Table -->
                    <div x-show="expanded" class="border-t border-slate-200 dark:border-slate-800 p-4 sm:p-5 bg-white dark:bg-slate-900">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-500 font-extrabold uppercase tracking-wider text-[11px]">
                                        <th class="py-2.5 px-3">Slot#</th>
                                        <th class="py-2.5 px-3">Slot Reference Name</th>
                                        <th class="py-2.5 px-3">Status</th>
                                        <th class="py-2.5 px-3">Occupying Collateral Item</th>
                                        <th class="py-2.5 px-3">Notes</th>
                                        <th class="py-2.5 px-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                                    <?php foreach ($r['slots'] as $s): ?>
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                            <td class="py-2.5 px-3 font-mono font-bold text-slate-900 dark:text-white">
                                                S<?= sprintf('%02d', $s['slot_number']) ?>
                                            </td>
                                            <td class="py-2.5 px-3 font-mono font-bold text-purple-700 dark:text-purple-400">
                                                <?= htmlspecialchars($s['slot_name']) ?>
                                            </td>
                                            <td class="py-2.5 px-3">
                                                <?php if ($s['is_occupied']): ?>
                                                    <span class="rounded-full bg-amber-500/10 px-2.5 py-0.5 text-[10px] font-black text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                                        Occupied
                                                    </span>
                                                <?php else: ?>
                                                    <span class="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-[10px] font-black text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                                        Available
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-2.5 px-3">
                                                <?php if ($s['is_occupied'] && !empty($s['item'])): ?>
                                                    <div>
                                                        <a href="<?= $baseUrl ?>/collateral/<?= $s['item']['item_id'] ?>" class="font-bold text-slate-900 dark:text-white hover:text-amber-600 underline">
                                                            <?= htmlspecialchars($s['item']['item_name']) ?>
                                                        </a>
                                                        <span class="text-[10px] text-slate-500 block">
                                                            Loan: <a href="<?= $baseUrl ?>/loans/<?= $s['item']['loan_id'] ?>" class="font-mono text-amber-600 dark:text-amber-400 font-bold"><?= htmlspecialchars($s['item']['loan_number']) ?></a> &bull; <?= htmlspecialchars($s['item']['customer_name']) ?>
                                                        </span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-slate-400 text-[11px] italic">Empty Slot</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-2.5 px-3 text-slate-500 text-[11px]">
                                                <?= htmlspecialchars($s['notes'] ?? '—') ?>
                                            </td>
                                            <td class="py-2.5 px-3 text-right">
                                                <div class="flex items-center justify-end gap-1.5">
                                                    
                                                    <?php if ($s['is_occupied'] && !empty($s['item'])): ?>
                                                        <!-- Relocate / Transfer Item Button -->
                                                        <button type="button" @click="transferItem = <?= htmlspecialchars(json_encode($s['item'])) ?>; transferModal = true" class="rounded-lg bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400 px-2 py-1 text-[11px] font-bold hover:bg-blue-100 transition" title="Transfer Item to another Slot">
                                                            <i class="fa-solid fa-arrow-right-arrow-left mr-1"></i> Move
                                                        </button>
                                                    <?php endif; ?>

                                                    <!-- Edit Slot Button -->
                                                    <button type="button" @click="currentSlot = <?= htmlspecialchars(json_encode($s)) ?>; editSlotModal = true" class="rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1 text-[11px] font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 transition" title="Edit Slot Details">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </button>

                                                    <!-- Delete Slot Button -->
                                                    <?php if (!$s['is_occupied']): ?>
                                                        <form action="<?= $baseUrl ?>/racks/slots/<?= $s['id'] ?>/delete" method="POST" onsubmit="return confirm('Delete slot <?= htmlspecialchars(addslashes($s['slot_name'])) ?>?')">
                                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                            <button type="submit" class="rounded-lg text-slate-400 hover:text-rose-500 p-1 transition" title="Delete Slot">
                                                                <i class="fa-solid fa-trash text-xs"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- MODALS: ADD RACK, EDIT RACK, ADD SLOT, EDIT SLOT, TRANSFER ITEM          -->
    <!-- ========================================================================= -->

    <!-- 1. Add New Rack Modal -->
    <div x-show="addRackModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4" @click.away="addRackModal = false">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-cubes text-blue-500"></i> Add New Storage Rack
                </h3>
                <button @click="addRackModal = false" class="text-slate-400 hover:text-slate-900 dark:hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form action="<?= $baseUrl ?>/racks/create" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Rack Number <span class="text-rose-500">*</span></label>
                        <input type="number" name="rack_number" min="1" value="<?= count($racks) + 1 ?>" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white font-mono font-bold focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Initial Slots Count <span class="text-rose-500">*</span></label>
                        <input type="number" name="total_slots" min="1" max="100" value="15" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white font-mono font-bold focus:border-amber-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Rack Title / Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" placeholder="e.g. Rack 11 or Vault Safe A" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white font-bold focus:border-amber-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Physical Location</label>
                        <input type="text" name="location" value="Main Vault Room"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Status</label>
                        <select name="status" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white font-bold focus:border-amber-500">
                            <option value="Active" selected>Active</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Disabled">Disabled</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Description / Notes</label>
                    <textarea name="description" rows="2" placeholder="Optional physical locker or safe specifications"
                              class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white focus:border-amber-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="addRackModal = false" class="rounded-xl px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-900">Cancel</button>
                    <button type="submit" class="rounded-xl bg-blue-600 hover:bg-blue-500 text-white px-5 py-2 text-xs font-black shadow">
                        Create Storage Rack
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. Edit Rack Modal -->
    <div x-show="editRackModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4" @click.away="editRackModal = false">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-pen text-amber-500"></i> Edit Storage Rack
                </h3>
                <button @click="editRackModal = false" class="text-slate-400 hover:text-slate-900 dark:hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form :action="'<?= $baseUrl ?>/racks/' + currentRack.id + '/update'" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Rack Name</label>
                    <input type="text" name="name" x-model="currentRack.name" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white font-bold focus:border-amber-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Physical Location</label>
                        <input type="text" name="location" x-model="currentRack.location"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Status</label>
                        <select name="status" x-model="currentRack.status" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white font-bold focus:border-amber-500">
                            <option value="Active">Active</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Disabled">Disabled</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                    <textarea name="description" rows="2" x-model="currentRack.description"
                              class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white focus:border-amber-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="editRackModal = false" class="rounded-xl px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-900">Cancel</button>
                    <button type="submit" class="rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 px-5 py-2 text-xs font-black shadow">
                        Update Rack
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. Add Slot to Rack Modal -->
    <div x-show="addSlotModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4" @click.away="addSlotModal = false">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-plus text-emerald-500"></i> Add New Slot to <span x-text="currentRack.name"></span>
                </h3>
                <button @click="addSlotModal = false" class="text-slate-400 hover:text-slate-900 dark:hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form :action="'<?= $baseUrl ?>/racks/' + currentRack.id + '/slots/create'" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Slot Number <span class="text-rose-500">*</span></label>
                        <input type="number" name="slot_number" min="1" :value="(currentRack.total_slots || 0) + 1" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white font-mono font-bold focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Status</label>
                        <select name="status" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white font-bold focus:border-amber-500">
                            <option value="Available" selected>Available</option>
                            <option value="Reserved">Reserved</option>
                            <option value="Disabled">Disabled</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Slot Name (Auto or Custom)</label>
                    <input type="text" name="slot_name" :placeholder="currentRack.name + ' - Slot ' + ((currentRack.total_slots || 0) + 1)"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white font-bold focus:border-amber-500">
                    <span class="text-[10px] text-slate-400">Leave blank to auto-format as 'Rack X - Slot Y'</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Slot Notes / Markings</label>
                    <input type="text" name="notes" placeholder="e.g. Bottom shelf heavy items only"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white focus:border-amber-500">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="addSlotModal = false" class="rounded-xl px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-900">Cancel</button>
                    <button type="submit" class="rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2 text-xs font-black shadow">
                        Add Slot
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 4. Edit Slot Modal -->
    <div x-show="editSlotModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4" @click.away="editSlotModal = false">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-pen text-amber-500"></i> Edit Slot Reference
                </h3>
                <button @click="editSlotModal = false" class="text-slate-400 hover:text-slate-900 dark:hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form :action="'<?= $baseUrl ?>/racks/slots/' + currentSlot.id + '/update'" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Slot Name</label>
                    <input type="text" name="slot_name" x-model="currentSlot.slot_name" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white font-mono font-bold focus:border-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Status</label>
                    <select name="status" x-model="currentSlot.status" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white font-bold focus:border-amber-500">
                        <option value="Available">Available</option>
                        <option value="Occupied">Occupied</option>
                        <option value="Reserved">Reserved</option>
                        <option value="Disabled">Disabled</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Notes / Physical Label</label>
                    <input type="text" name="notes" x-model="currentSlot.notes" placeholder="Optional notes"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2 px-3 text-xs text-slate-900 dark:text-white focus:border-amber-500">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="editSlotModal = false" class="rounded-xl px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-900">Cancel</button>
                    <button type="submit" class="rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 px-5 py-2 text-xs font-black shadow">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 5. Relocate / Transfer Collateral Item Modal -->
    <div x-show="transferModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4" @click.away="transferModal = false">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-arrow-right-arrow-left text-blue-500"></i> Relocate / Transfer Item
                </h3>
                <button @click="transferModal = false" class="text-slate-400 hover:text-slate-900 dark:hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs space-y-1">
                <div class="flex justify-between">
                    <span class="text-slate-500 font-bold">Item:</span>
                    <span class="font-extrabold text-slate-900 dark:text-white" x-text="transferItem.item_name"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500 font-bold">Current Slot:</span>
                    <span class="font-mono font-black text-purple-600 dark:text-purple-400" x-text="transferItem.rk_number"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500 font-bold">Loan#:</span>
                    <span class="font-mono text-amber-600 dark:text-amber-400 font-bold" x-text="transferItem.loan_number"></span>
                </div>
            </div>

            <form action="<?= $baseUrl ?>/racks/slots/transfer" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="item_id" :value="transferItem.item_id || transferItem.id">

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Select Target Slot</label>
                    <select name="new_slot_name" required class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 py-2.5 px-3 text-xs text-slate-900 dark:text-white font-mono font-bold focus:border-amber-500">
                        <option value="">-- Choose Available Slot --</option>
                        <?php foreach ($racks as $r): ?>
                            <optgroup label="<?= htmlspecialchars($r['name']) ?> (<?= $r['available_count'] ?> free)">
                                <?php foreach ($r['slots'] as $s): ?>
                                    <?php if (!$s['is_occupied']): ?>
                                        <option value="<?= htmlspecialchars($s['slot_name']) ?>">
                                            <?= htmlspecialchars($s['slot_name']) ?> (Available)
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="transferModal = false" class="rounded-xl px-4 py-2 text-xs font-bold text-slate-500 hover:text-slate-900">Cancel</button>
                    <button type="submit" class="rounded-xl bg-blue-600 hover:bg-blue-500 text-white px-5 py-2 text-xs font-black shadow">
                        Transfer Item Now
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- 6. Item Details Modal (for visualizer slot click) -->
<div id="itemDetailModal" class="fixed inset-0 z-50 hidden bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4" onclick="if (event.target === this) closeItemModal()">
    <div class="w-full max-w-md rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl relative">
        <button onclick="closeItemModal()" class="absolute right-4 top-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">
            <div id="modal-type-icon" class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/20 text-amber-500 text-lg font-bold">
                <i class="fa-solid fa-gem"></i>
            </div>
            <div>
                <h3 id="modal-item-name" class="font-black text-slate-900 dark:text-white text-base">Item Name</h3>
                <span id="modal-slot-name" class="text-xs font-mono font-black text-purple-600 dark:text-purple-400">Rack 1 - Slot 1</span>
            </div>
        </div>

        <div class="space-y-3 text-xs">
            <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                <span class="text-slate-500 font-bold">Loan Account</span>
                <a id="modal-loan-link" href="#" class="font-mono font-black text-amber-600 dark:text-amber-400 hover:underline">LMS-2026-0001</a>
            </div>
            <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                <span class="text-slate-500 font-bold">Customer Name</span>
                <span id="modal-cust-name" class="font-bold text-slate-900 dark:text-slate-200">Customer Name</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                <span class="text-slate-500 font-bold">Net Weight</span>
                <span id="modal-net-weight" class="font-mono font-black text-slate-900 dark:text-white">0.000 g</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                <span class="text-slate-500 font-bold">Purity (%)</span>
                <span id="modal-purity" class="font-bold text-amber-600 dark:text-amber-400">—</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                <span class="text-slate-500 font-bold">Market Valuation</span>
                <span id="modal-market-val" class="font-mono font-black text-emerald-600 dark:text-emerald-400">₹0.00</span>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-2">
            <button onclick="closeItemModal()" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition">
                Close
            </button>
            <a id="modal-view-item-btn" href="#" class="rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 px-4 py-2 text-xs font-black shadow transition">
                View Item Specs
            </a>
        </div>
    </div>
</div>

<script>
const baseUrl = '<?= $baseUrl ?>';

function filterRackGrid(filter) {
    document.querySelectorAll('.rack-filter-btn').forEach(btn => {
        btn.classList.remove('bg-amber-500', 'text-slate-950', 'shadow-sm', 'active');
        btn.classList.add('text-slate-700', 'dark:text-slate-400');
    });

    const activeBtn = document.getElementById('btn-filter-' + filter.toLowerCase());
    if (activeBtn) {
        activeBtn.classList.add('bg-amber-500', 'text-slate-950', 'shadow-sm', 'active');
        activeBtn.classList.remove('text-slate-700', 'dark:text-slate-400');
    }

    const slots = document.querySelectorAll('.slot-box');
    slots.forEach(slot => {
        const isOcc = slot.getAttribute('data-occupied') === '1';
        const type  = slot.getAttribute('data-type');

        if (filter === 'all') {
            slot.style.display = '';
        } else if (filter === 'occupied' && isOcc) {
            slot.style.display = '';
        } else if (filter === 'free' && !isOcc) {
            slot.style.display = '';
        } else if ((filter === 'GOLD' || filter === 'SILVER') && isOcc && type === filter) {
            slot.style.display = '';
        } else {
            slot.style.display = 'none';
        }
    });
}

function searchRackGrid() {
    const q = document.getElementById('rack-search-input').value.toLowerCase().trim();
    const slots = document.querySelectorAll('.slot-box');

    slots.forEach(slot => {
        const text = slot.getAttribute('data-search-text') || '';
        if (!q || text.includes(q)) {
            slot.style.opacity = '1';
            slot.classList.remove('ring-2', 'ring-amber-500');
            if (q && text.includes(q)) {
                slot.classList.add('ring-2', 'ring-amber-500');
            }
        } else {
            slot.style.opacity = '0.25';
            slot.classList.remove('ring-2', 'ring-amber-500');
        }
    });
}

function viewItemModal(item) {
    if (!item) return;

    document.getElementById('modal-item-name').innerText = item.item_name;
    document.getElementById('modal-slot-name').innerText = item.rk_number;
    document.getElementById('modal-cust-name').innerText = item.customer_name;
    document.getElementById('modal-net-weight').innerText = parseFloat(item.net_weight).toFixed(3) + ' g';
    const purityVal = item.purity_percentage && parseFloat(item.purity_percentage) > 0
        ? parseFloat(item.purity_percentage).toFixed(2) + '%'
        : (item.purity_preset || '—');
    document.getElementById('modal-purity').innerText = purityVal;
    
    const val = item.manual_market_value_override ? item.manual_market_value_override : item.market_value;
    document.getElementById('modal-market-val').innerText = '₹' + parseFloat(val).toLocaleString('en-IN', {minimumFractionDigits: 2});

    document.getElementById('modal-loan-link').innerText = item.loan_number;
    document.getElementById('modal-loan-link').href = baseUrl + '/loans/' + item.loan_id;

    document.getElementById('modal-view-item-btn').href = baseUrl + '/collateral/' + (item.item_id || item.id);

    const iconDiv = document.getElementById('modal-type-icon');
    if (item.item_type === 'GOLD') {
        iconDiv.className = 'flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/20 text-amber-500 text-lg font-bold';
        iconDiv.innerHTML = '<i class="fa-solid fa-gem"></i>';
    } else {
        iconDiv.className = 'flex h-10 w-10 items-center justify-center rounded-xl bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200 text-lg font-bold';
        iconDiv.innerHTML = '<i class="fa-solid fa-ring"></i>';
    }

    document.getElementById('itemDetailModal').classList.remove('hidden');
}

function closeItemModal() {
    document.getElementById('itemDetailModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
