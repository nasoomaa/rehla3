@if(session('success'))
    <div class="admin-toast toast-success" role="alert">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
        <div class="toast-message">{{ session('success') }}</div>
        <button type="button" class="toast-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
    </div>
@endif

@if(session('error'))
    <div class="admin-toast toast-danger" role="alert">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>
        </svg>
        <div class="toast-message">{{ session('error') }}</div>
        <button type="button" class="toast-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
    </div>
@endif

@if($errors->any())
    <div class="admin-toast toast-danger" role="alert">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>
        </svg>
        <div class="toast-message">
            <ul style="margin: 0; padding-inline-start: 1.25rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" class="toast-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
    </div>
@endif
