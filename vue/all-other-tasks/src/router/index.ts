import { createRouter, createWebHistory } from "vue-router";

const routes = [
  {
    path: "/",
    name: "home",
    component: () => import("@/pages/HomePage.vue"),
  },
  {
    path: "/add",
    name: "add",
    component: () => import("@/pages/AddResumePage.vue"),
  },
  {
    path: "/edit/:id",
    name: "edit",
    component: () => import("@/pages/EditResumePage.vue"),
    props: true,
  },
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
});

export default router;
