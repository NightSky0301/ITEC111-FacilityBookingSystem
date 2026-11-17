<!-- Confirmation pop-up (replaces the browser's confirm box). Any <form data-confirm="..."> opens it. -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content confirm-modal">
            <div class="confirm-head">
                <span class="confirm-icon" id="confirmIcon"><i class="bi bi-exclamation-triangle"></i></span>
                <h2 class="confirm-title" id="confirmTitle">Are you sure?</h2>
            </div>
            <div class="confirm-body">
                <p id="confirmText"></p>
            </div>
            <div class="confirm-foot">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="confirmCancel">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmOk">Confirm</button>
            </div>
        </div>
    </div>
</div>
