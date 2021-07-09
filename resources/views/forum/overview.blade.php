@php($subTitle = isset($activeTag) ? $activeTag->name() : null)
@title('Forum' . (isset($subTitle) ? ' > ' . $subTitle : ''))

@extends('layouts.default', ['hasShadow' => true])

@section('content')
    <div class="pt-5 pb-10 lg:pt-10 lg:pb-0">
        <div class="container mx-auto flex flex-col gap-x-12 px-4 lg:flex-row">
            <div class="lg:w-3/4">
                <div class="flex justify-between items-center lg:block">
                    <div class="flex justify-between items-center">
                        <h1 class="text-4xl text-gray-900 font-bold">Forum</h1>

                        <x-buttons.primary-button href="{{ route('threads.create') }}" class="hidden lg:block">
                            Create Thread
                        </x-buttons.primary-button>
                    </div>

                    <div class="flex justify-between lg:mt-6">
                        <h3 class="text-gray-800 text-xl font-semibold">18,185 Threads</h3>

                        <div class="flex hidden lg:block">
                            <x-threads.filter :filter="$filter" />
                        </div>
                    </div>
                </div>

                <div class="pt-2 lg:hidden">
                    @include('layouts._ads._forum_sidebar')

                    <div class="flex justify-center mt-6">
                        <x-buttons.dark-cta>
                            <x-heroicon-s-rss class="w-6 h-6 mr-2" />
                            RSS Feed
                        </x-buttons.dark-cta>
                    </div>

                    <div class="mt-10">
                        <x-buttons.primary-cta href="{{ route('threads.create') }}" class="w-full">
                            Create Thread
                        </x-buttons.primary-cta>
                    </div>

                    <div class="flex mt-4">
                        <x-threads.filter :filter="$filter" />
                    </div>
                </div>

                <section class="mt-8 mb-5 lg:mb-32">
                    <div class="flex flex-col gap-y-4">
                        @foreach ($threads as $thread)
                            <x-threads.overview-summary :thread="$thread" />
                        @endforeach
                    </div>

                    <div class="mt-10">
                        {{ $threads->onEachSide(1)->links() }}
                    </div>
                </section>
            </div>

            <div class="lg:w-1/4">
                <div class="hidden lg:block">
                    @include('layouts._ads._forum_sidebar')
                </div>

                <div class="bg-white shadow mt-6">
                    <h3 class="text-xl font-semibold px-5 pt-5">Thanks to our community</h3>
                    <ul>
                        @foreach ($topMembers as $member)
                            <li class="{{ ! $loop->last ? 'border-b ' : '' }}pb-3 pt-5">
                                <div class="flex justify-between items-center px-5">
                                    <div class="flex items-center gap-x-5">
                                        <x-avatar :user="$member" class="w-10 h-10" />
                                        <span class="flex flex-col">
                                            <a href="{{ route('profile', $member->username()) }}" class="hover:underline">
                                                <span class="text-gray-900 font-medium">
                                                    {{ $member->name() }}
                                                </span>
                                            </a>

                                            <span class="text-gray-700">
                                                {{ $member->solutions_count }} {{ Str::plural('Solution', $member->solutions_count) }}
                                            </span>
                                        </span>
                                    </div>
                                    
                                    <div>
                                        <span class="flex items-center gap-x-3 text-lio-500">
                                            <span class="text-xl font-medium">
                                                {{ $loop->iteration }}
                                            </span>
                                            <x-icon-trophy class="w-6 h-6" />
                                        </span>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="bg-white shadow mt-6">
                    <h3 class="text-xl font-semibold px-5 pt-5">Moderators</h3>
                    <ul>
                        @foreach ($moderators as $moderator)
                            <li class="{{ ! $loop->last ? 'border-b ' : '' }}flex items-center gap-x-5 pb-3 pt-5 px-5">
                                <x-avatar :user="$moderator" class="w-10 h-10" />
                                <span class="flex flex-col">
                                    <a href="{{ route('profile', $moderator->username()) }}" class="hover:underline">
                                        <span class="text-gray-900 font-medium">
                                            {{ $moderator->name() }}
                                        </span>
                                    </a>

                                    <span class="text-gray-700">
                                        Joined {{ $moderator->createdAt()->format('j M Y') }}
                                    </span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="hidden lg:block mt-6">
                    <x-buttons.dark-cta class="w-full">
                        <x-heroicon-s-rss class="w-6 h-6 mr-2" />
                        RSS Feed
                    </x-buttons.dark-cta>
                </div>
            </div>
        </div>
    </div>
@endsection
