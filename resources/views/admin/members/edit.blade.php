<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Anggota') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <a href="{{ route('admin.members.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
                ← Kembali ke Data Anggota
            </a>

            <div class="bg-white/95 backdrop-blur-sm rounded-xl border border-gray-100 shadow-sm p-6">
                <form method="POST" action="{{ route('admin.members.update', $member) }}">
                    @csrf
                    @method('PUT')
                    @include('admin.members._form')
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
