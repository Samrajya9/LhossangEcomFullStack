<?php
$PageHeaderShowExport = $PageHeaderShowExport ?? false;
$PageHeaderAddButtonId = $PageHeaderAddButtonId ?? 'addCustomerBtn';

?>
<div class="customer-container">
    <div class="page-header">
                <div>
                    <h2 class="page-title"><?php echo htmlspecialchars($pageSecondTitle); ?></h2>
                    <p class="page-subtitle"><?php echo htmlspecialchars($pageSecondSubTitle); ?></p>
                </div>
                <div class="header-actions">
                   <?php if ($PageHeaderShowExport): ?>
                <button class="btn btn-secondary" id="exportBtn">
                    <i class="fas fa-download"></i> Export
                </button>
            <?php endif; ?>

<button class="btn btn-primary" id="<?php echo htmlspecialchars($PageHeaderAddButtonId); ?>">
                        <i class="fas fa-plus"></i> Add <?php echo htmlspecialchars($PageHeaderAddButton); ?>
                    </button>
                </div>
            </div>

