<x-layouts.non-member :opd="$opd" :title="'Pesan Produk — ' . config('app.name')">
    {{-- Isi halamannya ada di x-order.picker, dipakai bareng form anggota.
         Yang khusus di sini cuma kolom identitas di slot bawah, karena
         non-anggota tidak punya akun personal yang menyimpan datanya. --}}
    <x-order.picker
        :period="$period"
        :products-by-category="$productsByCategory"
        :action="route('non-member.orders.store')"
        :batal="route('catalog.index')"
    >
        <x-slot:identitas>
            {{-- Nama & nomor WA diketik manual, dipakai admin buat kirim invoice --}}
            <x-card class="p-5 mb-4 space-y-4">
                <div>
                    <x-input-label for="non_member_name" value="Nama Kamu" />
                    <x-text-input
                        id="non_member_name"
                        name="non_member_name"
                        type="text"
                        class="block mt-1 w-full"
                        placeholder="Nama lengkap"
                        :value="old('non_member_name')"
                        required
                        autofocus
                    />
                    <x-input-error :messages="$errors->get('non_member_name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="whatsapp_number" value="Nomor WhatsApp" />
                    <x-text-input
                        id="whatsapp_number"
                        name="whatsapp_number"
                        type="text"
                        class="block mt-1 w-full"
                        placeholder="Contoh: 6281234567890"
                        :value="old('whatsapp_number')"
                        required
                    />
                    <p class="mt-1 text-xs text-gray-400">Buat kirim invoice belanja kamu nanti.</p>
                    <x-input-error :messages="$errors->get('whatsapp_number')" class="mt-2" />
                </div>
            </x-card>
        </x-slot:identitas>
    </x-order.picker>
</x-layouts.non-member>
