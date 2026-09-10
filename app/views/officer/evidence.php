<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-images text-primary"></i> Evidence Gallery</h2>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?php if (count($evidence) > 0): ?>
                <div class="row g-3">
                    <?php foreach (array_slice($evidence, 0, 20) as $file): ?>
                    <div class="col-md-3 col-sm-4 col-6">
                        <div class="card">
                            <img src="<?php echo APP_URL; ?>/storage/evidence/<?php echo e($file['filename']); ?>" class="card-img-top" alt="Evidence" style="height: 150px; object-fit: cover;">
                            <div class="card-body py-2">
                                <small class="text-muted"><?php echo e($file['filename']); ?></small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($evidence) > 20): ?>
                <p class="text-muted mt-3">Showing 20 of <?php echo count($evidence); ?> evidence files.</p>
                <?php endif; ?>
                <?php else: ?>
                <p class="text-muted">No evidence files found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
