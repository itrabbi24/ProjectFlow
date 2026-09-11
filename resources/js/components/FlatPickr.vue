<template>
  <input 
    type="text" 
    ref="input"
    :value="modelValue"
    :placeholder="placeholder"
  />
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.css";

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Select Date'
  },
  dateFormat: {
    type: String,
    default: 'Y-m-d'
  },
  altFormat: {
    type: String,
    default: 'd-M-Y'
  }
});

const emit = defineEmits(['update:modelValue']);
const input = ref(null);
let fp = null;

onMounted(() => {
  fp = flatpickr(input.value, {
    dateFormat: props.dateFormat,
    altInput: true,
    altFormat: props.altFormat,
    defaultDate: props.modelValue,
    onChange: (selectedDates, dateStr) => {
      emit('update:modelValue', dateStr);
    }
  });
});

watch(() => props.modelValue, (newVal) => {
  if (fp && newVal !== input.value?.value) {
    fp.setDate(newVal, false);
  }
});

onBeforeUnmount(() => {
  if (fp) {
    fp.destroy();
  }
});
</script>
