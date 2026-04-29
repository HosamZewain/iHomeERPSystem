<div class="space-y-6">
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
        <div class="flex items-start gap-3">
            <x-icon name="exclamation-triangle" class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600" />
            <div class="space-y-2">
                <p class="font-semibold">تحذير مهم</p>
                <p>الاستعادة الكاملة ستستبدل حالة قاعدة البيانات الحالية بالكامل. استخدم هذه الميزة فقط عند الضرورة وبعد التأكد من الملف المناسب.</p>
                <p>هذه الصفحة متاحة لمدير النظام فقط، وتحتاج أدوات <code>mysqldump</code> و <code>mysql</code> على الخادم حتى تعمل النسخ الاحتياطية والاستعادة.</p>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
        <x-card title="إنشاء نسخة احتياطية">
            <div class="space-y-4">
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                        <p class="text-xs text-gray-500">محرك قاعدة البيانات</p>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ $requirements['driver'] }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                        <p class="text-xs text-gray-500">أداة التصدير</p>
                        <p class="mt-1 text-sm font-medium text-gray-900 break-all">{{ $requirements['mysqldump'] ?: 'غير متاحة' }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                        <p class="text-xs text-gray-500">أداة الاستعادة</p>
                        <p class="mt-1 text-sm font-medium text-gray-900 break-all">{{ $requirements['mysql'] ?: 'غير متاحة' }}</p>
                    </div>
                </div>

                <p class="text-sm text-gray-500">سيتم إنشاء ملف SQL مضغوط يحتوي على بنية قاعدة البيانات والبيانات الحالية بالكامل، ثم حفظه داخل مساحة التخزين الآمنة للتطبيق.</p>

                <x-button type="button" wire:click="createBackup" class="w-full sm:w-auto">
                    إنشاء نسخة احتياطية جديدة
                </x-button>
            </div>
        </x-card>

        <x-card title="رفع ملف نسخة احتياطية">
            <form wire:submit="uploadBackup" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">ملف النسخة الاحتياطية</label>
                    <input wire:model="backupUpload" type="file" accept=".sql,.gz"
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700">
                    @error('backupUpload')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">الامتدادات المقبولة: {{ collect($acceptedExtensions)->map(fn ($ext) => '.' . $ext)->implode('، ') }}. الحد الأقصى الحالي 100MB لكل ملف.</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">ملاحظات</label>
                    <textarea wire:model="uploadNotes" rows="3"
                              class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"></textarea>
                    @error('uploadNotes')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <x-button type="submit" class="w-full sm:w-auto">رفع الملف</x-button>
            </form>
        </x-card>
    </div>

    @if($restoreBackupId)
        @php($restoreBackup = $backups->firstWhere('id', $restoreBackupId))
        <div class="rounded-xl border border-red-200 bg-red-50 p-4">
            <div class="flex items-start gap-3">
                <x-icon name="shield" class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-600" />
                <div class="min-w-0 flex-1 space-y-3">
                    <div>
                        <p class="text-sm font-semibold text-red-900">تأكيد استعادة قاعدة البيانات</p>
                        <p class="mt-1 text-sm text-red-800">
                            سيتم استبدال قاعدة البيانات الحالية بالملف:
                            <strong>{{ $restoreBackup?->original_file_name ?: $restoreBackup?->file_name }}</strong>
                        </p>
                    </div>

                    <div class="rounded-lg border border-red-200 bg-white/70 p-3 text-sm text-red-900">
                        <p>للاستمرار، اكتب كلمة <strong>{{ $restoreKeyword }}</strong> أو <strong>RESTORE</strong> في الحقل التالي.</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto_auto]">
                        <div>
                            <input wire:model="restoreConfirmation"
                                   type="text"
                                   class="block h-12 w-full rounded-lg border border-red-300 bg-white px-3 text-sm shadow-sm transition focus:border-red-500 focus:ring-2 focus:ring-red-500/20"
                                   placeholder="اكتب كلمة التأكيد هنا">
                            @error('restoreConfirmation')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <x-button type="button" wire:click="restoreSelectedBackup" variant="danger" class="h-12 w-full sm:w-auto">استعادة الآن</x-button>
                        <x-button type="button" wire:click="cancelRestore" variant="secondary" class="h-12 w-full sm:w-auto">إلغاء</x-button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <x-card title="النسخ الاحتياطية المتاحة" :padding="false">
        <div class="hidden lg:block overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الملف</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">النوع</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحجم</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">أنشئ بواسطة</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاريخ الإنشاء</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">آخر استعادة</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($backups as $backup)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $backup->original_file_name ?: $backup->file_name }}</div>
                                <div class="text-xs text-gray-500">{{ $backup->file_name }}</div>
                                @if($backup->notes)
                                    <div class="mt-1 text-xs text-gray-400">{{ $backup->notes }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $backup->sourceLabel() }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $backup->formattedFileSize() }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $backup->creator?->name ?: '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $backup->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                                @if($backup->restored_at)
                                    <div>{{ $backup->restored_at->format('Y-m-d H:i') }}</div>
                                    <div class="text-xs text-gray-500">{{ $backup->restorer?->name ?: '-' }}</div>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex flex-wrap items-center justify-end gap-3">
                                    <a href="{{ route('settings.backups.download', $backup) }}"
                                       class="text-primary-600 hover:text-primary-800 text-sm font-medium">
                                        تنزيل
                                    </a>
                                    <button type="button"
                                            wire:click="startRestore({{ $backup->id }})"
                                            class="text-red-600 hover:text-red-800 text-sm font-medium">
                                        استعادة
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-400">لا توجد نسخ احتياطية حتى الآن.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="space-y-3 p-4 lg:hidden">
            @forelse($backups as $backup)
                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ $backup->original_file_name ?: $backup->file_name }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $backup->sourceLabel() }} - {{ $backup->formattedFileSize() }}</p>
                            <p class="mt-1 text-xs text-gray-500">الإنشاء: {{ $backup->created_at->format('Y-m-d H:i') }}</p>
                            <p class="text-xs text-gray-500">بواسطة: {{ $backup->creator?->name ?: '-' }}</p>
                            @if($backup->restored_at)
                                <p class="mt-1 text-xs text-gray-500">آخر استعادة: {{ $backup->restored_at->format('Y-m-d H:i') }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-4 border-t border-gray-100 pt-3">
                        <a href="{{ route('settings.backups.download', $backup) }}"
                           class="text-primary-600 hover:text-primary-800 text-sm font-medium py-1">
                            تنزيل
                        </a>
                        <button type="button"
                                wire:click="startRestore({{ $backup->id }})"
                                class="text-red-600 hover:text-red-800 text-sm font-medium py-1">
                            استعادة
                        </button>
                    </div>
                </div>
            @empty
                <div class="rounded-lg border border-gray-200 p-8 text-center text-sm text-gray-400">لا توجد نسخ احتياطية حتى الآن.</div>
            @endforelse
        </div>
    </x-card>
</div>
