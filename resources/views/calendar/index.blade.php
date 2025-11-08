@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <div id="calendar" class="min-h-[600px] md:min-h-[700px]"></div>
    </div>

    <!-- Event Selection Modal -->
    <div id="eventSelectionModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full" style="z-index: 9999;">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-md shadow-lg rounded-md bg-white" style="position: relative; z-index: 10000;">
            <div class="mt-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="selection-modal-title">Events on <span id="selected-date"></span></h3>
                <div id="events-list" class="space-y-4 max-h-96 overflow-y-auto"></div>
                <div class="mt-4 flex justify-end space-x-2">
                    <button onclick="closeSelectionModal()" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Cancel
                    </button>
                    <button onclick="openAddEventModal()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Add New Event
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Form Modal -->
    <div id="eventModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full" style="z-index: 9999;">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-md shadow-lg rounded-md bg-white" style="position: relative; z-index: 10000;">
            <div class="mt-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Add Event</h3>
                <div class="mt-2 px-7 py-3">
                    <form id="eventForm">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                                Title
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="title" type="text" name="title" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Start Date & Time
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                id="start_datetime" 
                                type="text" 
                                name="start_datetime" 
                                placeholder="Select date and time"
                                required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                End Date & Time
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                id="end_datetime" 
                                type="text" 
                                name="end_datetime"
                                placeholder="Select date and time">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="color">
                                Color
                            </label>
                            <input class="shadow appearance-none border rounded w-full h-10" id="color" type="color" name="color" value="#3B82F6">
                        </div>
                        <input type="hidden" id="eventId">
                        <div class="flex items-center justify-end space-x-2 mt-4">
                            <button type="button" onclick="closeModal()" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Cancel
                            </button>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const events = {!! $eventsJson !!};
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const calendarEl = document.getElementById('calendar');
    const eventModal = document.getElementById('eventModal');
    const eventSelectionModal = document.getElementById('eventSelectionModal');
    const eventForm = document.getElementById('eventForm');
    const modalTitle = document.getElementById('modal-title');
    let selectedDate = null;
    let calendar;

    // Initialize Flatpickr
    const startPicker = flatpickr("#start_datetime", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        time_24hr: true,
        minuteIncrement: 15,
        onChange: function(selectedDates) {
            // Update end time minimum date when start date changes
            endPicker.set('minDate', selectedDates[0]);
        }
    });

    const endPicker = flatpickr("#end_datetime", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        time_24hr: true,
        minuteIncrement: 15
    });

    // Initialize FullCalendar
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: events,
        editable: true,
        selectable: true,
        dateClick: function(info) {
            selectedDate = info.date;
            const eventsOnDay = calendar.getEvents().filter(event => {
                const eventDate = new Date(event.start);
                return eventDate.toDateString() === selectedDate.toDateString();
            });

            if (eventsOnDay.length > 0) {
                showEventSelectionModal(eventsOnDay, info.dateStr);
            } else {
                openAddEventModal();
            }
        },
        eventClick: function(info) {
            openModal('edit', {
                id: info.event.id,
                title: info.event.title,
                start: info.event.startStr,
                end: info.event.endStr,
                color: info.event.backgroundColor
            });
        }
    });

    calendar.render();

    // Modal Functions
    function openModal(mode, data) {
        modalTitle.textContent = mode === 'add' ? 'Add Event' : 'Edit Event';
        eventForm.reset();

        if (mode === 'add') {
            deleteButton.classList.add('hidden');
            document.getElementById('eventId').value = '';
            document.getElementById('start').value = data.start;
        } else {
            deleteButton.classList.remove('hidden');
            document.getElementById('eventId').value = data.id;
            document.getElementById('title').value = data.title;
            document.getElementById('start').value = data.start?.slice(0, 16);
            document.getElementById('end').value = data.end?.slice(0, 16);
            document.getElementById('color').value = data.color || '#3B82F6';
        }

        eventModal.classList.remove('hidden');
    }

    window.closeModal = function() {
        eventModal.classList.add('hidden');
    }

    window.closeSelectionModal = function() {
        eventSelectionModal.classList.add('hidden');
    }

    function showEventSelectionModal(events, dateStr) {
        const eventsList = document.getElementById('events-list');
        const selectedDateSpan = document.getElementById('selected-date');
        
        // Format date for display
        selectedDateSpan.textContent = new Date(dateStr).toLocaleDateString();
        
        // Clear previous events
        eventsList.innerHTML = '';
        
        // Add each event to the list
        events.forEach(event => {
            const eventElement = document.createElement('div');
            eventElement.className = 'p-4 border rounded-lg shadow-sm hover:shadow-md transition-shadow';
            eventElement.style.borderLeftWidth = '4px';
            eventElement.style.borderLeftColor = event.backgroundColor || '#3B82F6';
            
            const timeStr = event.end ? 
                `${formatTime(event.start)} - ${formatTime(event.end)}` :
                formatTime(event.start);

            eventElement.innerHTML = `
                <div class="flex justify-between items-center">
                    <div>
                        <h4 class="font-semibold">${event.title}</h4>
                        <p class="text-sm text-gray-600">${timeStr}</p>
                    </div>
                    <div class="space-x-2">
                        <button onclick="editEvent('${event.id}')" class="text-blue-600 hover:text-blue-800 font-medium">
                            Edit
                        </button>
                        <button onclick="deleteEvent('${event.id}')" class="text-red-600 hover:text-red-800 font-medium">
                            Delete
                        </button>
                    </div>
                </div>
            `;
            
            eventsList.appendChild(eventElement);
        });
        
        eventSelectionModal.classList.remove('hidden');
    }

    function formatTime(date) {
        return new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    window.openAddEventModal = function() {
        closeSelectionModal();
        const now = new Date();
        selectedDate.setHours(now.getHours(), now.getMinutes());
        
        modalTitle.textContent = 'Add Event';
        eventForm.reset();
        document.getElementById('eventId').value = '';
        
        // Set default date and time values
        startPicker.setDate(selectedDate);
        
        // Set end time to 1 hour after start by default
        const endDate = new Date(selectedDate.getTime() + 60 * 60 * 1000);
        endPicker.setDate(endDate);
        
        eventModal.classList.remove('hidden');
    }

    window.editEvent = function(eventId) {
        const event = calendar.getEventById(eventId);
        if (!event) return;

        closeSelectionModal();
        modalTitle.textContent = 'Edit Event';
        
        document.getElementById('eventId').value = eventId;
        document.getElementById('title').value = event.title;
        
        // Set start date and time
        startPicker.setDate(event.start);
        
        // Set end date and time if available
        if (event.end) {
            endPicker.setDate(event.end);
        } else {
            endPicker.clear();
        }
        
        document.getElementById('color').value = event.backgroundColor || '#3B82F6';
        
        eventModal.classList.remove('hidden');
    }

    window.deleteEvent = async function(eventId) {
        if (!confirm('Are you sure you want to delete this event?')) return;

        try {
            const response = await fetch(`/events/${eventId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            if (!response.ok) {
                throw new Error('Failed to delete event');
            }

            const event = calendar.getEventById(eventId);
            if (event) {
                event.remove();
            }

            closeSelectionModal();
        } catch (error) {
            console.error('Error:', error);
            alert('There was an error deleting the event');
        }
    }

    // Form Submission
    eventForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(eventForm);
        const eventId = document.getElementById('eventId').value;
        
        // Format dates properly
        const startDate = flatpickr.parseDate(formData.get('start_datetime'), "Y-m-d H:i");
        const endDate = formData.get('end_datetime') ? 
            flatpickr.parseDate(formData.get('end_datetime'), "Y-m-d H:i") : null;
            
        const data = {
            title: formData.get('title'),
            start: startDate ? startDate.toISOString() : null,
            end: endDate ? endDate.toISOString() : null,
            color: formData.get('color')
        };
        
        // Validate required fields
        if (!data.start) {
            alert('Start date and time are required');
            return;
        }

        // Validate required fields
        if (!data.title || !data.start) {
            alert('Title and Start time are required');
            return;
        }

        try {
            const url = eventId ? `/events/${eventId}` : '/events';
            const method = eventId ? 'PUT' : 'POST';
            
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to save event');
            }

            const event = await response.json();
            if (eventId) {
                const existingEvent = calendar.getEventById(eventId);
                if (existingEvent) {
                    existingEvent.remove();
                }
            }

            calendar.addEvent({
                id: event.id,
                title: event.title,
                start: event.start,
                end: event.end,
                color: event.color
            });

            closeModal();
        } catch (error) {
            console.error('Error:', error);
            alert('There was an error saving the event');
        }
    });

    // Delete Event
    deleteButton.addEventListener('click', async function() {
        const eventId = document.getElementById('eventId').value;
        if (!eventId) return;

        if (confirm('Are you sure you want to delete this event?')) {
            try {
                const response = await fetch(`/events/${eventId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const existingEvent = calendar.getEventById(eventId);
                if (existingEvent) {
                    existingEvent.remove();
                }

                closeModal();
            } catch (error) {
                console.error('Error:', error);
                alert('There was an error deleting the event');
            }
        }
    });
});
</script>
@endsection