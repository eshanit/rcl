<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-900 dark:to-slate-800 p-6 lg:p-8">
        <!-- Header -->
        <header class="max-w-7xl mx-auto w-full mb-12 lg:mb-16">
            <nav class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg"></div>
                    <span class="font-semibold text-slate-900 dark:text-white">Rucola SMART</span>
                </div>
                
                <div class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="px-6 py-2.5 rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200 font-medium hover:shadow-lg transition-all duration-300 hover:scale-105"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="px-6 py-2.5 rounded-xl text-slate-700 dark:text-slate-300 font-medium hover:text-slate-900 dark:hover:text-white transition-colors duration-300"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="route('register')"
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-blue-500 to-purple-600 text-white font-medium hover:shadow-lg transition-all duration-300 hover:scale-105"
                        >
                            Get Started
                        </Link>
                    </template>
                </div>
            </nav>
        </header>

        <!-- Main Content -->
        <div class="flex items-center justify-center min-h-[70vh]">
            <main class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <!-- Text Content -->
                <div class="space-y-8">
                    <div class="space-y-4">
                        <div class="inline-flex items-center px-4 py-2 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-sm font-medium mb-2">
                            Healthcare Analytics Platform
                        </div>
                        <h1 class="text-4xl lg:text-6xl font-bold text-slate-900 dark:text-white leading-tight">
                            Advanced HIV Care
                            <span class="bg-gradient-to-r from-blue-500 to-purple-600 bg-clip-text text-transparent">Analytics</span>
                        </h1>
                        <p class="text-xl text-slate-600 dark:text-slate-300 leading-relaxed">
                            Rucola SMART is a comprehensive web-based dashboard designed to monitor and analyze ART program performance and patient care. 
                            Gain real-time insights into treatment outcomes and enhance quality of care through data-driven decision making.
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4">
                        <Link
                            :href="route('register')"
                            class="px-8 py-4 rounded-xl bg-gradient-to-r from-blue-500 to-purple-600 text-white font-semibold hover:shadow-xl transition-all duration-300 hover:scale-105 text-center"
                        >
                            Start Monitoring
                        </Link>
                        <Link
                            :href="route('login')"
                            class="px-8 py-4 rounded-xl bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-semibold hover:shadow-lg transition-all duration-300 text-center"
                        >
                            SMART Dashboard
                        </Link>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-8 pt-8 border-t border-slate-200 dark:border-slate-700">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-slate-900 dark:text-white">99%</div>
                            <div class="text-sm text-slate-500 dark:text-slate-400">Accuracy</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-slate-900 dark:text-white">24/7</div>
                            <div class="text-sm text-slate-500 dark:text-slate-400">Monitoring</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-slate-900 dark:text-white">100+</div>
                            <div class="text-sm text-slate-500 dark:text-slate-400">Clinics</div>
                        </div>
                    </div>
                </div>

                <!-- Visual Element -->
                <div class="relative">
                    <div class="relative z-10 bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-2xl border border-slate-200 dark:border-slate-700">
                        <div class="space-y-6">
                            <!-- Mock chart -->
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <div class="h-3 bg-slate-200 dark:bg-slate-600 rounded-full w-3/4"></div>
                                    <div class="h-3 bg-slate-200 dark:bg-slate-600 rounded-full w-1/5"></div>
                                </div>
                                <div class="flex justify-between items-center">
                                    <div class="h-3 bg-blue-500 rounded-full w-4/5"></div>
                                    <div class="h-3 bg-blue-500 rounded-full w-1/4"></div>
                                </div>
                                <div class="flex justify-between items-center">
                                    <div class="h-3 bg-purple-500 rounded-full w-2/3"></div>
                                    <div class="h-3 bg-purple-500 rounded-full w-1/3"></div>
                                </div>
                            </div>
                            
                            <!-- Mock data cards -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-slate-50 dark:bg-slate-700 rounded-xl p-4">
                                    <div class="text-sm text-slate-500 dark:text-slate-400">Patient Adherence</div>
                                    <div class="text-xl font-bold text-slate-900 dark:text-white">94%</div>
                                </div>
                                <div class="bg-slate-50 dark:bg-slate-700 rounded-xl p-4">
                                    <div class="text-sm text-slate-500 dark:text-slate-400">Viral Load</div>
                                    <div class="text-xl font-bold text-slate-900 dark:text-white">87%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Background decorative elements -->
                    <div class="absolute -top-4 -right-4 w-24 h-24 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl opacity-20 blur-xl"></div>
                    <div class="absolute -bottom-4 -left-4 w-32 h-32 bg-gradient-to-r from-purple-500 to-blue-600 rounded-2xl opacity-20 blur-xl"></div>
                </div>
            </main>
        </div>

        <!-- Footer -->
        <footer class="max-w-7xl mx-auto w-full mt-16 text-center text-slate-500 dark:text-slate-400 text-sm">
            <p>© 2024 Rucola SMART. Advanced healthcare analytics for better patient outcomes.</p>
        </footer>
    </div>
</template>