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
  }
});

const emit = defineEmits(['update:modelValue']);
const input = ref(null);
let fp = null;

onMounted(() => {
  fp = flatpickr(input.value, {
    dateFormat: "d-M-Y",
    defaultDate: props.modelValue,
    onChange: (selectedDates, dateStr) => {
      emit('update:modelValue', dateStr);
    }
  });
});

watch(() => props.modelValue, (newVal) => {
  if (fp && newVal !== input.value.value) {
    fp.setDate(newVal, false);
  }
});

onBeforeUnmount(() => {
  if (fp) {
    fp.destroy();
  }
});
</script>
