<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
let meters = ref<number>(0.0);
let foots = ref<number>(0.0);
let inputMeters = ref<boolean>(false);
let inputFoots = ref<boolean>(false);
let misterFocus = ref<HTMLElement>();

watch(inputFoots, () => {
  if (!inputFoots.value) {
    meters.value = foots.value / 0.3048;
  }
})
watch(inputMeters, () => {
  if (!inputMeters.value) {
    foots.value = meters.value * 0.3048;
  }
})

onMounted(() => {
  document.addEventListener("keypress", (key) => {
    if (key.code == "Enter" || key.code == "Escape") {
      if (!misterFocus.value) return;
      misterFocus.value.focus()
      console.log(misterFocus);
    }
  })
})
</script>

<template>
  <div class="main-wrapper">
    <h1 class="header">Конвертер метров в футы</h1>
    <div class="input-wrapper">
      <input type="number" v-on:focusin="() => { inputMeters = true }" v-on:focusout="() => { inputMeters = false }"
        v-model="meters" placeholder="метры" />
      <input type="number" v-on:focusin="() => { inputFoots = true }" v-on:focusout="() => { inputFoots = false }"
        v-model="foots" placeholder="футы" />
    </div>
  </div>
  <div ref="misterFocus" tabindex="-1" style="outline: none;"></div>
</template>

<style scoped>
.header {
  color: #eb6f92;
}

.main-wrapper {
  padding: 0;
  margin: 0;
  min-height: 98vh;
  width: 100%;
  background-color: #232136;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 3em;
}

.input-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 3em;
}

input {
  background-color: rgba(0, 0, 0, 0);
  outline: none;
  border: 1px solid #eb6f92;
  padding: 1em;
  font-size: 1em;
  color: #eb6f92;
  border-radius: 25px;
}
</style>
