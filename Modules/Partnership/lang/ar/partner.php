<?php

return [
    // General
    'partners' => 'شركاء',
    'partner' => 'شريك',
    'partner_list' => 'قائمة الشركاء',
    'create_partner' => 'إنشاء شريك',
    'edit_partner' => 'تعديل الشريك',
    'partner_details' => 'تفاصيل الشريك',
    'partner_update' => 'تحديث الشريك',
    'partner_created_successfully' => 'تم إنشاء الشريك بنجاح',
    'partner_updated_successfully' => 'تم تحديث الشريك بنجاح',
    'partners_deleted_successfully' => 'تم حذف :count شريك(شركاء) بنجاح',
    'add_partner' => 'إضافة شريك',
    // 'list_partners' => 'قائمة الشركاء',

    // Partner Types
    'partner_type' => 'نوع الشريك',
    'individual' => 'فردي',
    'business' => 'تجاري',
    'organization' => 'منظمة',
    'income_from' => 'الدخل من',

    // Fields
    'company_name' => 'اسم الشركة',
    'enter_company_name' => 'أدخل اسم الشركة',
    'contact_person' => 'الشخص المسؤول',
    'enter_contact_person' => 'أدخل اسم الشخص المسؤول',
    'designation' => 'المنصب',
    'enter_designation' => 'أدخل المنصب',
    'website' => 'الموقع الإلكتروني',
    'enter_website' => 'أدخل رابط الموقع الإلكتروني',
    'set_credit_limit' => 'تعيين حد الائتمان',
    'credit_limit' => 'حد الائتمان',
    'enter_credit_limit' => 'أدخل مبلغ حد الائتمان',
    'default_partner' => 'تعيين كشريك افتراضي',

    // Sections
    'basic_information' => 'المعلومات الأساسية',
    'contact_information' => 'معلومات الاتصال',
    'tax_information' => 'المعلومات الضريبية',
    'financial_information' => 'المعلومات المالية',
    'address_information' => 'معلومات العنوان',
    'additional_information' => 'معلومات إضافية',
    'status_information' => 'معلومات الحالة',

    // Messages
    'partner_not_found' => 'الشريك غير موجود',
    'partner_already_exists' => 'الشريك موجود بالفعل',
    'cannot_delete_partner' => 'لا يمكن حذف الشريك لأنه قيد الاستخدام',
    'credit_limit_required' => 'حد الائتمان مطلوب عند تفعيل حد الائتمان',
    'invalid_partner_type' => 'نوع شريك غير صالح',

    // Validation Messages
    'partner_type_required' => 'نوع الشريك مطلوب',
    'first_name_required' => 'الاسم الأول مطلوب',
    'company_name_required' => 'اسم الشركة مطلوب للشركاء التجاريين/المنظمات',
    'contact_person_required' => 'الشخص المسؤول مطلوب للشركاء التجاريين/المنظمات',
    'email_invalid' => 'يرجى تقديم عنوان بريد إلكتروني صالح',
    'website_invalid' => 'يرجى تقديم رابط موقع صالح',
    'mobile_invalid' => 'يرجى تقديم رقم جوال صالح',
    'phone_invalid' => 'يرجى تقديم رقم هاتف صالح',
    'whatsapp_invalid' => 'يرجى تقديم رقم واتساب صالح',
    'credit_limit_invalid' => 'يجب أن يكون حد الائتمان رقماً موجباً',
    'exchange_rate_invalid' => 'يجب أن يكون سعر الصرف رقماً موجباً',

    // Status Messages
    'partner_active' => 'الشريك نشط',
    'partner_inactive' => 'الشريك غير نشط',
    'partner_default' => 'هذا هو الشريك الافتراضي',
    'partner_not_default' => 'هذا ليس الشريك الافتراضي',

    // Actions
    'view_partner' => 'عرض الشريك',
    'edit_partner' => 'تعديل الشريك',
    'delete_partner' => 'حذف الشريك',
    'activate_partner' => 'تفعيل الشريك',
    'deactivate_partner' => 'تعطيل الشريك',
    'set_as_default' => 'تعيين كافتراضي',
    'remove_default' => 'إزالة الافتراضي',

    // Filters
    'filter_by_type' => 'تصفية حسب نوع الشريك',
    'filter_by_status' => 'تصفية حسب الحالة',
    'all_types' => 'جميع الأنواع',
    'all_statuses' => 'جميع الحالات',

    // Statistics
    'total_partners' => 'إجمالي الشركاء',
    'active_partners' => 'الشركاء النشطون',
    'inactive_partners' => 'الشركاء غير النشطين',
    'individual_partners' => 'الشركاء الأفراد',
    'business_partners' => 'الشركاء التجاريون',
    'organization_partners' => 'الشركاء المنظمات',

    // Contract Module
    'contract_list' => 'قائمة العقود',
    'select_partner_for_item' => 'يرجى اختيار شريك للعنصر :item',

    'share_holders' => 'مساهمون',
    'active_partners_and_share_details' => 'الشركاء النشطون وتفاصيل الأسهم',

    'allocations' => 'التخصيصات',
    'payment_allocations' => 'تخصيص الدفع',
    'party_payment_allocation' => 'تخصيص دفع الطرف',
    'allocate_to_partner' => 'تخصيص للشريك',
    'distribute_amount_to_partner_message' => 'توزيع المبلغ على الشريك (الشركاء) المحدد',
    'party_opening_balance_allocation' => 'تخصيص الرصيد الافتتاحي للطرف',
    'allocation_amount_exceeds_payment_amount' => 'يتجاوز مبلغ التخصيص مبلغ دفع الطرف.',
    'failed_to_delete_partner_party_transaction' => 'فشل في حذف معاملة شريك الطرف.',
    'allocated_party_transaction' => 'معاملة الطرف المخصصة',
    'allocated_payment_to_partner' => 'دفع مخصص للشريك',
    'allocated_amount' => 'المبلغ المخصص',
    'unallocated' => 'غير مخصص',
    'party_manual_payments_and_remaining_balance_allocation' => 'الدفعات اليدوية للطرف وتخصيص الرصيد المتبقي (بعد التخصيص)',
    'party_balance_allocation' => 'تخصيص رصيد الطرف',
    'party_opening_balance_allocation' => 'تخصيص الرصيد الافتتاحي للطرف',
    'party_allocation' => 'تخصيص الطرف',
    'current_items' => 'عناصر الشريك الحالية',
    'items' => 'عناصر الشريك',
    'show_currently_active_items_of_partner' => 'عرض عناصر الشريك النشطة حاليًا (الحالة)',
    'profit_report' => 'تقرير ربح الشريك',
    'profit_report_item_wise' => 'تقرير ربح عنصر الشريك',
    'detailed_partner_item_profit' => 'ربح عنصر الشريك التفصيلي',
    'settlement_list' => 'قائمة التسويات',
    'partner_settlement_list' => 'قائمة تسوية الشريك',
    'settlements' => 'التسويات',
    'create_settlement' => 'إنشاء تسوية',
    'edit_settlement' => 'تعديل التسوية',
    'settlement_code' => 'رمز التسوية',
    'update_settlement' => 'تحديث التسوية',
    'partnership_module' => 'وحدة الشراكة',
    'view_partners' => 'عرض الشركاء',
    'view_settlement' => 'عرض التسوية',
    'delete_settlement' => 'حذف التسوية',
    'party_payment_allocation_to_partner' => 'تخصيص دفع الطرف للشريك',
    'delete_party_payment_allocation_to_partner' => 'حذف تخصيص دفع الطرف للشريك',
    'partnership_report' => 'تقارير وحدة الشراكة',
    'settlement_report' => 'تقرير التسوية',

];
