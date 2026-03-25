<script setup></script>

<template>
  <div
    class="min-h-screen overflow-x-hidden bg-gray-100 px-4 py-12 sm:px-6 lg:px-8 [&_button]:min-h-11 [&_a.inline-flex]:min-h-11"
  >
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <h1 class="text-center text-3xl font-bold text-primary-600">Drawing🏗️low</h1>
      <p class="mt-2 text-center text-sm text-gray-600">Steel Fabrication Shop Drawing Workflow</p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow-sm sm:rounded-lg sm:px-10 border border-gray-200">
        <slot />
      </div>
    </div>
  </div>
</template>
