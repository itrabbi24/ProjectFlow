<template>
  <div class="relative">
    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
      <CalendarDays class="w-3.5 h-3.5 text-slate-400" />
    </div>
    <input
      ref="inputRef"
      type="text"
      :placeholder="placeholder"
      :class="[
        'w-full pl-8 pr-3 py-1.5 border border-slate-200 rounded-lg text-xs text-slate-800',
        'placeholder-slate-400 focus:outline-none focus:border-indigo-400 bg-white cursor-pointer',
        inputClass
      ]"
      readonly
    />
    <button
      v-if="modelValue"
      type="button"
      @click.stop="clear"
      class="absolute inset-y-0 right-2 flex items-center text-slate-300 hover:text-slate-500"
    >
      <X class="w-3 h-3" />
    </button>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import { CalendarDays, X } from 'lucide-vue-next';

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Select date'
  },
  mode: {
    type: String,
    default: 'single' // single | range | multiple
  },
  enableTime: {
    type: Boolean,
    default: false
  },
  minDate: {
    type: String,
    default: null
  },
  maxDate: {
    type: String,
    default: null
  },
  dateFormat: {
    type: String,
    default: 'Y-m-d'
  },
  altFormat: {
    type: String,
    default: 'd M Y'
  },
  inputClass: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['update:modelValue', 'change']);
const inputRef = ref(null);
let fpInstance = null;

onMounted(() => {
  fpInstance = flatpickr(inputRef.value, {
    mode: props.mode,
    enableTime: props.enableTime,
    dateFormat: props.dateFormat,
    altInput: true,
    altFormat: props.altFormat,
    defaultDate: props.modelValue || null,
    minDate: props.minDate,
    maxDate: props.maxDate,
    disableMobile: true,
    onChange(selectedDates, dateStr) {
      emit('update:modelValue', dateStr);
      emit('change', dateStr, selectedDates);
    },
    onReady(_, __, fp) {
      // Light mode calendar style overrides
      if (fp.calendarContainer) {
        fp.calendarContainer.style.fontFamily = 'inherit';
        fp.calendarContainer.style.fontSize = '12px';
        fp.calendarContainer.style.boxShadow = '0 10px 40px rgba(0,0,0,0.12)';
        fp.calendarContainer.style.borderRadius = '12px';
        fp.calendarContainer.style.border = '1px solid #e2e8f0';
      }
    }
  });
});

watch(() => props.modelValue, (val) => {
  if (fpInstance && val !== fpInstance.input.value) {
    fpInstance.setDate(val || null, false);
  }
});

watch(() => props.minDate, (val) => {
  fpInstance?.set('minDate', val);
});

watch(() => props.maxDate, (val) => {
  fpInstance?.set('maxDate', val);
});

function clear() {
  fpInstance?.clear();
  emit('update:modelValue', '');
  emit('change', '', []);
}

onUnmounted(() => {
  fpInstance?.destroy();
});
</script>

<style>
/* Flatpickr light mode theme overrides */
.flatpickr-calendar {
  font-family: inherit !important;
  border: 1px solid #e2e8f0 !important;
  border-radius: 12px !important;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.10) !important;
  background: #ffffff !important;
  padding: 8px !important;
}
.flatpickr-calendar.arrowTop::before,
.flatpickr-calendar.arrowTop::after {
  border-bottom-color: #e2e8f0 !important;
}
.flatpickr-months .flatpickr-month {
  background: #ffffff !important;
  color: #1e293b !important;
  fill: #1e293b !important;
}
.flatpickr-current-month {
  color: #1e293b !important;
  font-size: 13px !important;
  font-weight: 700 !important;
}
.flatpickr-current-month .numInputWrapper span.arrowUp::after {
  border-bottom-color: #64748b;
}
.flatpickr-current-month .numInputWrapper span.arrowDown::after {
  border-top-color: #64748b;
}
.flatpickr-weekdays {
  background: #ffffff !important;
}
span.flatpickr-weekday {
  color: #94a3b8 !important;
  font-size: 10px !important;
  font-weight: 700 !important;
  letter-spacing: 0.05em !important;
  background: #ffffff !important;
}
.flatpickr-day {
  color: #374151 !important;
  border-radius: 8px !important;
  font-size: 11px !important;
  font-weight: 500 !important;
}
.flatpickr-day:hover {
  background: #f1f5f9 !important;
  border-color: transparent !important;
}
.flatpickr-day.today {
  border-color: #6366f1 !important;
  color: #6366f1 !important;
  font-weight: 700 !important;
}
.flatpickr-day.selected,
.flatpickr-day.startRange,
.flatpickr-day.endRange {
  background: #6366f1 !important;
  border-color: #6366f1 !important;
  color: #ffffff !important;
  font-weight: 700 !important;
}
.flatpickr-day.inRange {
  background: #eef2ff !important;
  border-color: transparent !important;
  box-shadow: -5px 0 0 #eef2ff, 5px 0 0 #eef2ff !important;
  color: #4f46e5 !important;
}
.flatpickr-day.flatpickr-disabled,
.flatpickr-day.prevMonthDay,
.flatpickr-day.nextMonthDay {
  color: #cbd5e1 !important;
}
.flatpickr-prev-month,
.flatpickr-next-month {
  color: #64748b !important;
  fill: #64748b !important;
}
.flatpickr-prev-month:hover,
.flatpickr-next-month:hover {
  color: #4f46e5 !important;
  fill: #4f46e5 !important;
}
.flatpickr-input[readonly] {
  cursor: pointer !important;
}
.flatpickr-input.flatpickr-input.active {
  border-color: #6366f1 !important;
}
</style>
