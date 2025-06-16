<template>
    <img
        :src="'/images/ltpc-logo.png'"
        alt="LTPC - Livelihood Training and Productivity Center"
        class="object-contain"
        :class="$attrs.class"
    />
</template>

<script>
export default {
    name: "ApplicationLogo",
    inheritAttrs: false,
};
</script>
