<div class="space-y-4 text-sm">
    <div class="grid grid-cols-2 gap-3 text-xs">
        <div><strong>Type:</strong> {{ $import->import_type }}</div>
        <div><strong>Status:</strong> {{ $import->status }}</div>
        <div><strong>File:</strong> {{ $import->file_name }}</div>
        <div><strong>By:</strong> {{ $import->user?->name ?? '—' }}</div>
        <div><strong>Uploaded:</strong> {{ $import->created_at }}</div>
        <div><strong>Committed:</strong> {{ $import->committed_at ?? 'Not committed' }}</div>
    </div>

    <div class="grid grid-cols-4 gap-3 text-xs bg-gray-50 dark:bg-gray-900 p-3 rounded">
        <div><strong>Total:</strong> {{ $import->rows_total }}</div>
        <div class="text-green-700"><strong>Valid:</strong> {{ $import->rows_valid }}</div>
        <div class="text-red-700"><strong>Invalid:</strong> {{ $import->rows_invalid }}</div>
        <div class="text-gray-600"><strong>Skipped:</strong> {{ $import->rows_skipped }}</div>
    </div>

    @if (! empty($import->error_summary))
        <div>
            <h4 class="text-xs font-semibold mb-2">First {{ count($import->error_summary) }} errors</h4>
            <div class="text-xs bg-red-50 dark:bg-red-950 border border-red-200 rounded p-2 max-h-64 overflow-auto">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($import->error_summary as $err)
                        <li>
                            <strong>Row {{ $err['row'] ?? '?' }}:</strong>
                            {{ implode('; ', $err['errors'] ?? []) }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
</div>
