import React from 'react';
import { Link } from '@inertiajs/react';
import { Trophy, Flame, Star, Award } from 'lucide-react';

export default function XPCard({ user }) {
    const {
        total_xp = 0,
        level = 1,
        level_title = 'Emerging Business',
        xp_for_next_level = 100,
        xp_progress_percentage = 0,
        current_streak = 0,
        recent_badges = [],
    } = user || {};

    return (
        <div className="bg-gradient-to-br from-teal-500 via-teal-600 to-teal-700 rounded-2xl p-6 text-white shadow-xl relative overflow-hidden">
            {/* Wave pattern background */}
            <svg 
                className="absolute inset-0 w-full h-full opacity-10 pointer-events-none"
                viewBox="0 0 400 200"
                preserveAspectRatio="none"
            >
                <defs>
                    <pattern id="wave-pattern" x="0" y="0" width="50" height="50" patternUnits="userSpaceOnUse">
                        <circle cx="25" cy="25" r="1.5" fill="white" />
                    </pattern>
                </defs>
                <rect width="400" height="200" fill="url(#wave-pattern)" />
                <path
                    d="M0,100 C50,120 100,80 150,100 C200,120 250,80 300,100 C350,120 400,80 400,100 L400,200 L0,200 Z"
                    fill="white"
                    opacity="0.15"
                >
                    <animate
                        attributeName="d"
                        dur="6s"
                        repeatCount="indefinite"
                        values="
                            M0,100 C50,120 100,80 150,100 C200,120 250,80 300,100 C350,120 400,80 400,100 L400,200 L0,200 Z;
                            M0,110 C50,90 100,130 150,110 C200,90 250,130 300,110 C350,90 400,130 400,110 L400,200 L0,200 Z;
                            M0,100 C50,120 100,80 150,100 C200,120 250,80 300,100 C350,120 400,80 400,100 L400,200 L0,200 Z
                        "
                    />
                </path>
                <path
                    d="M0,130 C50,150 100,110 150,130 C200,150 250,110 300,130 C350,150 400,110 400,130 L400,200 L0,200 Z"
                    fill="white"
                    opacity="0.08"
                >
                    <animate
                        attributeName="d"
                        dur="8s"
                        repeatCount="indefinite"
                        values="
                            M0,130 C50,150 100,110 150,130 C200,150 250,110 300,130 C350,150 400,110 400,130 L400,200 L0,200 Z;
                            M0,140 C50,120 100,160 150,140 C200,120 250,160 300,140 C350,120 400,160 400,140 L400,200 L0,200 Z;
                            M0,130 C50,150 100,110 150,130 C200,150 250,110 300,130 C350,150 400,110 400,130 L400,200 L0,200 Z
                        "
                    />
                </path>
            </svg>

            {/* Floating circles decoration */}
            <div className="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full blur-2xl transform translate-x-8 -translate-y-8" />
            <div className="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full blur-xl transform -translate-x-6 translate-y-6" />
            
            {/* Header */}
            <div className="flex items-center justify-between mb-6 relative z-10">
                <div className="flex items-center gap-2">
                    <Star className="w-5 h-5 text-yellow-300" />
                    <p className="text-sm font-medium text-white/90">Your Progress</p>
                </div>
                {/* Shaking trophy icon */}
                <Trophy className="w-8 h-8 text-yellow-300/80 animate-wiggle" />
            </div>

            {/* XP Display */}
            <div className="relative z-10 mb-6">
                <p className="text-4xl font-black tracking-tight">{total_xp.toLocaleString()} XP</p>
                <p className="text-sm text-white/80 mt-1">{level_title}</p>
            </div>

            {/* Level Progress */}
            <div className="relative z-10 mb-6">
                <div className="flex items-center justify-between text-sm mb-2">
                    <span className="flex items-center gap-1.5">
                        <Star className="w-4 h-4 text-yellow-300" />
                        <span className="font-semibold">Level {level}</span>
                    </span>
                    <span className="text-white/80">
                        {xp_for_next_level} XP to Level {level + 1}
                    </span>
                </div>
                <div className="w-full bg-white/20 rounded-full h-2.5 overflow-hidden backdrop-blur-sm">
                    <div 
                        className="bg-gradient-to-r from-yellow-300 to-yellow-400 h-2.5 rounded-full transition-all duration-1000 ease-out relative overflow-hidden"
                        style={{ width: `${xp_progress_percentage}%` }}
                    >
                        {/* Shimmer effect */}
                        <div className="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer" />
                    </div>
                </div>
            </div>

            {/* Streak */}
            <div className="relative z-10 flex items-center justify-between pt-4 border-t border-white/20">
                <div className="flex items-center gap-2 bg-white/10 rounded-full px-3 py-1.5 backdrop-blur-sm">
                    <Flame className={`w-4 h-4 text-orange-300 ${current_streak > 0 ? 'animate-pulse-flame' : ''}`} />
                    <span className="text-sm font-semibold">
                        {current_streak} day streak
                    </span>
                </div>
                {recent_badges && recent_badges.length > 0 && (
                    <div className="flex -space-x-1">
                        {recent_badges.slice(0, 3).map((badge, index) => (
                            <div 
                                key={index}
                                className="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center text-base backdrop-blur-sm border-2 border-white/30"
                                title={badge.name}
                            >
                                {badge.icon}
                            </div>
                        ))}
                    </div>
                )}
            </div>

        </div>
    );
}
