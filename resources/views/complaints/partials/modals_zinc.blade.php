<!-- Resolve Complaint Modal -->
<div id="resolveModal" x-cloak class="hidden fixed inset-0 bg-brand-950/40 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
    <div class="relative mx-auto p-0 border-0 w-full max-w-md shadow-2xl rounded-2xl bg-white overflow-hidden">
        <div class="px-6 py-4 border-b border-brand-100 bg-brand-50/50 flex justify-between items-center">
            <h3 class="text-sm font-bold text-brand-800 uppercase tracking-widest">Mark as Resolved</h3>
            <button onclick="document.getElementById('resolveModal').classList.add('hidden')" class="text-brand-400 hover:text-brand-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
        </div>
        
        <form method="POST" action="{{ route('complaints.resolve', $complaint) }}" class="p-6">
            @csrf
            <div class="mb-5">
                <x-input-label value="Resolution Note" class="mb-2 text-xs font-bold uppercase text-brand-500" />
                <textarea name="resolution_note" rows="4" required 
                    placeholder="Describe how the issue was resolved..."
                    class="w-full border-brand-200 rounded-xl focus:ring-emerald-500/10 focus:border-emerald-500 text-sm placeholder:text-brand-300"></textarea>
                <p class="mt-2 text-[10px] text-brand-400 italic">This note will be visible to the resident.</p>
            </div>

            <div class="flex gap-3">
                <x-secondary-button type="button" onclick="document.getElementById('resolveModal').classList.add('hidden')" class="flex-1 justify-center">
                    Cancel
                </x-secondary-button>
                <x-primary-button class="flex-1 justify-center !bg-emerald-600 hover:!bg-emerald-700">
                    Confirm Resolve
                </x-primary-button>
            </div>
        </form>
    </div>
</div>

<!-- Dispute / Reopen Modal -->
<div id="disputeModal" x-cloak class="hidden fixed inset-0 bg-brand-950/60 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
    <div class="relative mx-auto p-0 border-0 w-full max-w-md shadow-2xl rounded-2xl bg-white overflow-hidden border-t-4 border-rose-500">
        <div class="px-6 py-4 border-b border-brand-100 bg-rose-50/30 flex justify-between items-center">
            <h3 class="text-sm font-bold text-rose-800 uppercase tracking-widest italic">⚠️ Escalation Request</h3>
            <button onclick="document.getElementById('disputeModal').classList.add('hidden')" class="text-rose-300 hover:text-rose-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
        </div>
        
        <form method="POST" action="{{ route('complaints.dispute', $complaint) }}" enctype="multipart/form-data" class="p-6">
            @csrf
            <div class="mb-4">
                <x-input-label value="Reason for Dispute" class="mb-2 text-xs font-bold uppercase text-rose-600" />
                <textarea name="reason" rows="4" required 
                    placeholder="Explain why the problem is still not fixed..."
                    class="w-full border-rose-200 rounded-xl focus:ring-rose-500/10 focus:border-rose-500 text-sm placeholder:text-rose-200"></textarea>
            </div>

            <div class="mb-6">
                <x-input-label value="Proof / Photo (Optional)" class="mb-2 text-xs font-bold uppercase text-brand-500" />
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-brand-200 border-dashed rounded-xl hover:border-brand-300 transition cursor-pointer relative">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-8 w-8 text-brand-300" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-brand-600">
                            <span class="relative font-bold text-rose-600">Upload files</span>
                            <input name="files[]" type="file" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        </div>
                        <p class="text-xxs text-brand-400 uppercase">PNG, JPG, JPEG up to 5MB</p>
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <x-secondary-button type="button" onclick="document.getElementById('disputeModal').classList.add('hidden')" class="flex-1 justify-center">
                    Go Back
                </x-secondary-button>
                <x-primary-button class="flex-1 justify-center !bg-rose-600 hover:!bg-rose-700 !text-white font-bold">
                    Escalate Now
                </x-primary-button>
            </div>
        </form>
    </div>
</div>