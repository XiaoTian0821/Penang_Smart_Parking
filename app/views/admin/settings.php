<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="fas fa-cog text-primary"></i> System Settings</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">AI Configuration</h5></div>
            <div class="card-body">
                <form>
                    <div class="mb-3">
                        <label class="form-label">Confidence Threshold</label>
                        <input type="number" class="form-control" value="0.75" step="0.01" min="0" max="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Primary Model</label>
                        <input type="text" class="form-control" value="gemini-2.0-flash" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fallback Model</label>
                        <input type="text" class="form-control" value="gemini-1.5-flash" readonly>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Parking Rules</h5></div>
            <div class="card-body">
                <form>
                    <div class="mb-3">
                        <label class="form-label">Default Max Duration (minutes)</label>
                        <input type="number" class="form-control" value="120">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Compound Amount - Not Paid (RM)</label>
                        <input type="number" class="form-control" value="10.00" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Compound Amount - Expired (RM)</label>
                        <input type="number" class="form-control" value="10.00" step="0.01">
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <button class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
    </div>
</div>
