<div class="space-y-6">
    <div class="mb-4">
        <a href="{{ route('settings.print') }}" wire:navigate
           class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            <x-icon name="arrow-left" class="h-4 w-4 ml-1" />
            الرجوع إلى قوالب الطباعة
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <x-card title="معلومات القالب">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <x-input label="اسم القالب" wire:model="name" type="text" required :error="$errors->first('name')" />
                <x-input label="كود القالب" wire:model="code" type="text" placeholder="يُنشأ تلقائيًا عند تركه فارغًا" :error="$errors->first('code')" />
                <x-select label="نوع المستند" wire:model.live="document_type" required :error="$errors->first('document_type')" :disabled="$isEditing">
                    @foreach($documentTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-select>
                <x-input label="الترتيب" wire:model="sort_order" type="number" min="0" required :error="$errors->first('sort_order')" />
                <x-input label="عنوان الطباعة" wire:model="title" type="text" required :error="$errors->first('title')" />
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات داخلية</label>
                <textarea wire:model="notes" rows="3"
                          class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border"></textarea>
            </div>

            <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-3">
                <x-checkbox label="قالب نشط" description="القوالب غير النشطة لا تظهر في اختيار الطباعة." wire:model="is_active" />
                <x-checkbox label="القالب الافتراضي لهذا النوع" description="عند التفعيل يتم إلغاء الافتراضي من القوالب الأخرى لنفس النوع." wire:model="is_default" />
            </div>
        </x-card>

        <x-card title="الهوية والصور">
            <x-checkbox label="استخدام بيانات الشركة العامة للحقول الفارغة" description="يمكنك رفع صور أو كتابة بيانات خاصة بالقالب، وتستخدم البيانات العامة فقط عند ترك الحقل فارغًا." wire:model="settings.company.use_global_identity" />

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <x-input label="اسم الشركة / المعرض" wire:model="settings.company.name" type="text" :error="$errors->first('settings.company.name')" />
                <x-input label="مسار الشعار أو رابطه اليدوي" wire:model="settings.company.logo_path" type="text" placeholder="/images/logo.png" :error="$errors->first('settings.company.logo_path')" />
                <x-input label="مسار صورة الترويسة أو رابطها اليدوي" wire:model="settings.company.header_image_path" type="text" placeholder="/images/print-header.png" :error="$errors->first('settings.company.header_image_path')" />
                <x-input label="مسار صورة التذييل أو رابطها اليدوي" wire:model="settings.company.footer_image_path" type="text" placeholder="/images/print-footer.png" :error="$errors->first('settings.company.footer_image_path')" />
                <x-input label="الهاتف" wire:model="settings.company.phone" type="text" :error="$errors->first('settings.company.phone')" />
                <x-input label="البريد الإلكتروني" wire:model="settings.company.email" type="email" :error="$errors->first('settings.company.email')" />
                <x-input label="الموقع الإلكتروني" wire:model="settings.company.website" type="text" :error="$errors->first('settings.company.website')" />
                <x-input label="وسم العملة" wire:model="settings.company.currency_label" type="text" required :error="$errors->first('settings.company.currency_label')" />
                <x-input label="الرقم الضريبي" wire:model="settings.company.tax_number" type="text" :error="$errors->first('settings.company.tax_number')" />
                <x-input label="رقم السجل التجاري" wire:model="settings.company.registration_number" type="text" :error="$errors->first('settings.company.registration_number')" />
            </div>

            <div class="mt-5 grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">رفع الشعار</label>
                    <input wire:model="logoUpload" type="file" accept="image/*"
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700 file:ml-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-gray-700">
                    @if($errors->has('logoUpload'))
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('logoUpload') }}</p>
                    @endif
                    <p class="mt-1 text-xs text-gray-500">PNG أو JPG حتى 2MB. يحفظ المسار تلقائيًا عند الحفظ.</p>
                    @if(filled($settings['company']['logo_path'] ?? null))
                        <img src="{{ $settings['company']['logo_path'] }}" alt="الشعار الحالي" class="mt-3 h-16 max-w-full rounded border border-gray-200 object-contain">
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">رفع صورة الترويسة</label>
                    <input wire:model="headerImageUpload" type="file" accept="image/*"
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700 file:ml-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-gray-700">
                    @if($errors->has('headerImageUpload'))
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('headerImageUpload') }}</p>
                    @endif
                    <p class="mt-1 text-xs text-gray-500">صورة عريضة اختيارية حتى 4MB.</p>
                    @if(filled($settings['company']['header_image_path'] ?? null))
                        <img src="{{ $settings['company']['header_image_path'] }}" alt="صورة الترويسة الحالية" class="mt-3 h-20 w-full rounded border border-gray-200 object-contain">
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">رفع صورة التذييل</label>
                    <input wire:model="footerImageUpload" type="file" accept="image/*"
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700 file:ml-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-gray-700">
                    @if($errors->has('footerImageUpload'))
                        <p class="mt-1 text-xs text-red-600">{{ $errors->first('footerImageUpload') }}</p>
                    @endif
                    <p class="mt-1 text-xs text-gray-500">صورة عريضة اختيارية حتى 4MB.</p>
                    @if(filled($settings['company']['footer_image_path'] ?? null))
                        <img src="{{ $settings['company']['footer_image_path'] }}" alt="صورة التذييل الحالية" class="mt-3 h-20 w-full rounded border border-gray-200 object-contain">
                    @endif
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">العنوان</label>
                <textarea wire:model="settings.company.address" rows="2"
                          class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border"></textarea>
            </div>

            <div class="mt-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                <x-checkbox label="إظهار الشعار" wire:model="settings.company.show_logo" />
                <x-checkbox label="إظهار صورة الترويسة" wire:model="settings.company.show_header_image" />
                <x-checkbox label="إظهار صورة التذييل" wire:model="settings.company.show_footer_image" />
                <x-checkbox label="إظهار الهاتف" wire:model="settings.company.show_phone" />
                <x-checkbox label="إظهار البريد الإلكتروني" wire:model="settings.company.show_email" />
                <x-checkbox label="إظهار العنوان" wire:model="settings.company.show_address" />
                <x-checkbox label="إظهار الموقع الإلكتروني" wire:model="settings.company.show_website" />
                <x-checkbox label="إظهار الرقم الضريبي" wire:model="settings.company.show_tax_number" />
                <x-checkbox label="إظهار السجل التجاري" wire:model="settings.company.show_registration_number" />
            </div>
        </x-card>

        <x-card title="خيارات التصميم">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <x-select label="مقاس الورق" wire:model="settings.layout.paper_size" required :error="$errors->first('settings.layout.paper_size')">
                    @foreach($options['paper_size'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-select>
                <x-select label="محاذاة الترويسة" wire:model="settings.layout.header_alignment" required :error="$errors->first('settings.layout.header_alignment')">
                    @foreach($options['header_alignment'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-select>
                <x-select label="المسافات" wire:model="settings.layout.spacing" required :error="$errors->first('settings.layout.spacing')">
                    @foreach($options['spacing'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-select>
                <x-select label="حجم الخط" wire:model="settings.layout.font_size" required :error="$errors->first('settings.layout.font_size')">
                    @foreach($options['font_size'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-select>
                <x-select label="كثافة الجدول" wire:model="settings.layout.table_density" required :error="$errors->first('settings.layout.table_density')">
                    @foreach($options['table_density'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-select>
                <x-select label="حجم الشعار" wire:model="settings.layout.logo_size" required :error="$errors->first('settings.layout.logo_size')">
                    @foreach($options['logo_size'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-select>
                <x-select label="هوامش الصفحة" wire:model="settings.layout.margin" required :error="$errors->first('settings.layout.margin')">
                    @foreach($options['margin'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-select>
            </div>
        </x-card>

        <x-card title="النصوص العامة">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">نص التذييل العام</label>
                    <textarea wire:model="settings.general.footer_text" rows="4"
                              class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">رسالة الشكر</label>
                    <textarea wire:model="settings.general.thank_you_message" rows="4"
                              class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تنبيه / إخلاء مسؤولية</label>
                    <textarea wire:model="settings.general.disclaimer" rows="4"
                              class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border"></textarea>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                <x-checkbox label="إظهار رسالة الشكر" wire:model="settings.general.show_thank_you_message" />
                <x-checkbox label="إظهار التنبيه / إخلاء المسؤولية" wire:model="settings.general.show_disclaimer" />
            </div>
        </x-card>

        <x-card title="إظهار وإخفاء محتوى المستند">
            @if($document_type === \App\Models\PrintTemplate::TYPE_QUOTATION)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">نص تذييل عرض السعر</label>
                    <textarea wire:model="settings.quotation.footer_text" rows="3"
                              class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border"></textarea>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">الشروط داخل الصفحة الأولى</label>
                    <textarea wire:model="settings.quotation.terms" rows="3"
                              class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border"></textarea>
                </div>
                <div class="mt-4">
                    <x-input label="اسم بند التركيب داخل جدول المنتجات" wire:model="settings.quotation.installation_item_name" type="text" required :error="$errors->first('settings.quotation.installation_item_name')" />
                </div>

                <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                    <x-checkbox label="إظهار اسم العميل" wire:model="settings.quotation.show_customer_name" />
                    <x-checkbox label="إظهار هاتف العميل" wire:model="settings.quotation.show_customer_phone" />
                    <x-checkbox label="إظهار بريد العميل" wire:model="settings.quotation.show_customer_email" />
                    <x-checkbox label="إظهار عنوان العميل" wire:model="settings.quotation.show_customer_address" />
                    <x-checkbox label="إظهار رقم العرض" wire:model="settings.quotation.show_number" />
                    <x-checkbox label="إظهار تاريخ العرض" wire:model="settings.quotation.show_date" />
                    <x-checkbox label="إظهار الحالة" wire:model="settings.quotation.show_status" />
                    <x-checkbox label="إظهار مسؤول البيع" wire:model="settings.quotation.show_creator" />
                    <x-checkbox label="إظهار صور المنتجات" wire:model="settings.quotation.show_product_images" />
                    <x-checkbox label="إظهار عمود خصم البند" wire:model="settings.quotation.show_item_discounts" />
                    <x-checkbox label="إظهار الإجمالي الفرعي" wire:model="settings.quotation.show_subtotal" />
                    <x-checkbox label="إظهار خصم إجمالي المستند" wire:model="settings.quotation.show_invoice_discount" />
                    <x-checkbox label="إظهار قسم التركيب" wire:model="settings.quotation.show_installation" />
                    <x-checkbox label="إظهار الإجمالي النهائي" wire:model="settings.quotation.show_total" />
                    <x-checkbox label="إظهار ملاحظات العرض" wire:model="settings.quotation.show_notes" />
                    <x-checkbox label="إظهار الشروط داخل الصفحة الأولى" wire:model="settings.quotation.show_terms" />
                </div>
            @elseif($document_type === \App\Models\PrintTemplate::TYPE_SALES_INVOICE)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">نص تذييل فاتورة البيع</label>
                    <textarea wire:model="settings.sales_invoice.footer_text" rows="3"
                              class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border"></textarea>
                </div>
                <div class="mt-4">
                    <x-input label="اسم بند التركيب داخل جدول المنتجات" wire:model="settings.sales_invoice.installation_item_name" type="text" required :error="$errors->first('settings.sales_invoice.installation_item_name')" />
                </div>

                <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                    <x-checkbox label="إظهار اسم العميل" wire:model="settings.sales_invoice.show_customer_name" />
                    <x-checkbox label="إظهار هاتف العميل" wire:model="settings.sales_invoice.show_customer_phone" />
                    <x-checkbox label="إظهار بريد العميل" wire:model="settings.sales_invoice.show_customer_email" />
                    <x-checkbox label="إظهار عنوان العميل" wire:model="settings.sales_invoice.show_customer_address" />
                    <x-checkbox label="إظهار رقم الفاتورة" wire:model="settings.sales_invoice.show_number" />
                    <x-checkbox label="إظهار تاريخ الفاتورة" wire:model="settings.sales_invoice.show_date" />
                    <x-checkbox label="إظهار الحالة" wire:model="settings.sales_invoice.show_status" />
                    <x-checkbox label="إظهار مسؤول البيع" wire:model="settings.sales_invoice.show_creator" />
                    <x-checkbox label="إظهار مرجع عرض السعر" wire:model="settings.sales_invoice.show_quotation_reference" />
                    <x-checkbox label="إظهار صور المنتجات" wire:model="settings.sales_invoice.show_product_images" />
                    <x-checkbox label="إظهار عمود خصم البند" wire:model="settings.sales_invoice.show_item_discounts" />
                    <x-checkbox label="إظهار الإجمالي الفرعي" wire:model="settings.sales_invoice.show_subtotal" />
                    <x-checkbox label="إظهار خصم إجمالي المستند" wire:model="settings.sales_invoice.show_invoice_discount" />
                    <x-checkbox label="إظهار قسم التركيب" wire:model="settings.sales_invoice.show_installation" />
                    <x-checkbox label="إظهار إجمالي فاتورة العميل" wire:model="settings.sales_invoice.show_gross_total" />
                    <x-checkbox label="إظهار ملاحظات الفاتورة" wire:model="settings.sales_invoice.show_notes" />
                </div>
            @else
                <p class="text-sm text-gray-500">تم حجز هذا النوع للتوسع لاحقًا.</p>
            @endif
        </x-card>

        <x-card title="صفحة شروط الضمان">
            <x-checkbox label="إظهار صفحة شروط الضمان كصفحة ثانية" description="تظهر بعد صفحة عرض السعر أو الفاتورة مع فاصل صفحة واضح عند الطباعة." wire:model="settings.warranty.enabled" />

            <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                <x-input label="عنوان صفحة الضمان" wire:model="settings.warranty.title" type="text" required :error="$errors->first('settings.warranty.title')" />
                <x-input label="تذييل صفحة الضمان" wire:model="settings.warranty.footer_text" type="text" :error="$errors->first('settings.warranty.footer_text')" />
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">محتوى شروط الضمان</label>
                <textarea wire:model="settings.warranty.body" rows="8"
                          class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3 border"></textarea>
                @if($errors->has('settings.warranty.body'))
                    <p class="mt-1 text-xs text-red-600">{{ $errors->first('settings.warranty.body') }}</p>
                @endif
                <p class="mt-1 text-xs text-gray-500">يتم عرض النص كسطور آمنة بدون HTML مخصص.</p>
            </div>
        </x-card>

        <div class="sticky bottom-0 z-10 -mx-4 border-t border-gray-200 bg-white/95 px-4 py-3 shadow-lg backdrop-blur sm:mx-0 sm:rounded-lg sm:border">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3">
                <x-button wire:click="loadDefaults" type="button" variant="secondary" class="w-full sm:w-auto">
                    تحميل الافتراضي
                </x-button>
                <a href="{{ route('settings.print') }}" wire:navigate
                   class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                    إلغاء
                </a>
                <x-button type="submit" class="w-full sm:w-auto" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ $isEditing ? 'تحديث القالب' : 'إنشاء القالب' }}</span>
                    <span wire:loading>جار الحفظ...</span>
                </x-button>
            </div>
        </div>
    </form>
</div>
