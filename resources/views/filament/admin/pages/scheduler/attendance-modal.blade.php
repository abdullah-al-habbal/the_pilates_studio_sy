<div class="space-y-6 py-4">
    {{-- Attendees List --}}
    <div class="space-y-4">
        <h3 class="text-lg font-bold flex items-center gap-2">
            <x-heroicon-o-users class="w-5 h-5 text-gray-400" />
            Confirmed Attendees
        </h3>

        @php
            $bookings = $session->bookingSessions()->with('booking.user')->get();
        @endphp

        @if($bookings->isEmpty())
            <div class="text-center py-6 bg-gray-50 dark:bg-gray-800 rounded-lg border border-dashed border-gray-300 dark:border-gray-700">
                <p class="text-gray-500">No reservations yet for this session.</p>
            </div>
        @else
            <div class="divide-y divide-gray-200 dark:divide-gray-700 border rounded-lg overflow-hidden bg-white dark:bg-gray-900 shadow-sm">
                @foreach($bookings as $bookingSession)
                    <div class="flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-bold">
                                {{ substr($bookingSession->booking->user->fullname, 0, 1) }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $bookingSession->booking->user->fullname }}</p>
                                <p class="text-xs text-gray-500">{{ $bookingSession->booking->user->phone_number }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2" x-data="{ status: '{{ $bookingSession->attendance_status->value ?? 'missed' }}' }">
                            <button
                                type="button"
                                @click="$wire.toggleAttendance({{ $bookingSession->id }}, 'attended'); status = 'attended'"
                                :class="status === 'attended' ? 'bg-success-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
                                class="px-3 py-1.5 rounded-md text-sm font-medium transition-all hover:scale-105 active:scale-95"
                            >
                                Attended
                            </button>
                            <button
                                type="button"
                                @click="$wire.toggleAttendance({{ $bookingSession->id }}, 'missed'); status = 'missed'"
                                :class="status === 'missed' ? 'bg-danger-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
                                class="px-3 py-1.5 rounded-md text-sm font-medium transition-all hover:scale-105 active:scale-95"
                            >
                                Missed
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Walk-in Section --}}
    <div class="pt-6 border-t border-gray-200 dark:border-gray-700 space-y-4">
        <h3 class="text-lg font-bold flex items-center gap-2 text-primary-600">
            <x-heroicon-o-user-plus class="w-5 h-5" />
            Add Walk-in Attendee
        </h3>
        
        <div class="flex items-end gap-3" x-data="{ userId: null }">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search Member</label>
                <select 
                    x-model="userId"
                    class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="">Select a member...</option>
                    @foreach(\App\Models\User::all() as $user)
                        <option value="{{ $user->id }}">{{ $user->fullname }} ({{ $user->phone_number }})</option>
                    @endforeach
                </select>
            </div>
            
            <button
                type="button"
                @click="if(userId) { $wire.addWalkIn({{ $session->id }}, userId); userId = null; }"
                :disabled="!userId"
                class="px-4 py-2 bg-primary-600 text-white rounded-md font-medium hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
                Attend Now
            </button>
        </div>
        <p class="text-xs text-gray-500">
            Note: "Attend Now" will automatically create a 1-session walk-in pass and mark the user as attended.
        </p>
    </div>
</div>
