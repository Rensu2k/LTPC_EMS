<!--
  LTPC Enrollment Management System (LTPC_EMS)

  Copyright (c) 2025-2026 Clarence Buenaflor & Jester Pastor. All rights reserved.
  Unauthorized copying or distribution is strictly prohibited.
-->
<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import * as THREE from "three";

const canvasRef = ref(null);
let scene, camera, renderer, particles, iceSphere, iceBox, animationId;

function init() {
    const canvas = canvasRef.value;
    if (!canvas) return;

    scene = new THREE.Scene();
    scene.background = new THREE.Color(0xe8f4f8);
    scene.fog = new THREE.FogExp2(0xe8f4f8, 0.012);

    camera = new THREE.PerspectiveCamera(
        75,
        window.innerWidth / window.innerHeight,
        0.1,
        1000
    );
    camera.position.z = 8;

    renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

    // Soft ambient + directional lighting (frosty feel)
    const ambient = new THREE.AmbientLight(0xb8d4e3, 0.6);
    scene.add(ambient);

    const dirLight = new THREE.DirectionalLight(0xffffff, 0.5);
    dirLight.position.set(5, 5, 5);
    scene.add(dirLight);

    const fillLight = new THREE.DirectionalLight(0xa8d8ea, 0.3);
    fillLight.position.set(-3, 2, 4);
    scene.add(fillLight);

    // Ice-like sphere (frosty accent)
    const sphereGeo = new THREE.SphereGeometry(1.2, 32, 32);
    const sphereMat = new THREE.MeshPhongMaterial({
        color: 0xb8d4e3,
        transparent: true,
        opacity: 0.15,
        shininess: 80,
        specular: 0xffffff,
    });
    iceSphere = new THREE.Mesh(sphereGeo, sphereMat);
    iceSphere.position.set(-2, 0.5, -4);
    scene.add(iceSphere);

    // Second smaller ice shape
    const boxGeo = new THREE.BoxGeometry(0.8, 0.8, 0.8);
    const boxMat = new THREE.MeshPhongMaterial({
        color: 0xa8d8ea,
        transparent: true,
        opacity: 0.12,
        shininess: 90,
        specular: 0xffffff,
    });
    iceBox = new THREE.Mesh(boxGeo, boxMat);
    iceBox.position.set(2.5, -0.8, -5);
    iceBox.rotation.y = 0.5;
    scene.add(iceBox);

    // Particle system
    const particleCount = 400;
    const positions = new Float32Array(particleCount * 3);
    const colors = new Float32Array(particleCount * 3);
    const iceColor = new THREE.Color(0xb8d4e3);
    const frostColor = new THREE.Color(0xd4eaf2);

    for (let i = 0; i < particleCount; i++) {
        positions[i * 3] = (Math.random() - 0.5) * 20;
        positions[i * 3 + 1] = (Math.random() - 0.5) * 20;
        positions[i * 3 + 2] = (Math.random() - 0.5) * 15;

        const mix = Math.random();
        colors[i * 3] = iceColor.r * mix + frostColor.r * (1 - mix);
        colors[i * 3 + 1] = iceColor.g * mix + frostColor.g * (1 - mix);
        colors[i * 3 + 2] = iceColor.b * mix + frostColor.b * (1 - mix);
    }

    const particleGeo = new THREE.BufferGeometry();
    particleGeo.setAttribute("position", new THREE.BufferAttribute(positions, 3));
    particleGeo.setAttribute("color", new THREE.BufferAttribute(colors, 3));

    const particleMat = new THREE.PointsMaterial({
        size: 0.08,
        vertexColors: true,
        transparent: true,
        opacity: 0.6,
        sizeAttenuation: true,
    });

    particles = new THREE.Points(particleGeo, particleMat);
    scene.add(particles);
}

function animate() {
    animationId = requestAnimationFrame(animate);

    const t = performance.now() * 0.001;

    if (iceSphere) {
        iceSphere.rotation.y += 0.002;
        iceSphere.position.y = 0.5 + Math.sin(t * 0.5) * 0.1;
    }

    if (particles) {
        particles.rotation.y += 0.0003;
    }

    if (renderer && scene && camera) {
        renderer.render(scene, camera);
    }
}

function onResize() {
    if (!camera || !renderer) return;
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
}

onMounted(() => {
    init();
    animate();
    window.addEventListener("resize", onResize);
});

onBeforeUnmount(() => {
    window.removeEventListener("resize", onResize);
    if (animationId) cancelAnimationFrame(animationId);
    if (particles?.geometry) particles.geometry.dispose();
    if (particles?.material) particles.material.dispose();
    if (iceSphere?.geometry) iceSphere.geometry.dispose();
    if (iceSphere?.material) iceSphere.material.dispose();
    if (iceBox?.geometry) iceBox.geometry.dispose();
    if (iceBox?.material) iceBox.material.dispose();
    renderer?.dispose();
});
</script>

<template>
    <canvas ref="canvasRef" class="webgl-canvas" aria-hidden="true" />
</template>

<style scoped>
.webgl-canvas {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 0;
    pointer-events: none;
}
</style>
