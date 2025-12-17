// AddyOnboarding.jsx - Beautiful animated onboarding experience
import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Head, router } from '@inertiajs/react';
import { 
  Sparkles, ArrowRight, Check, Users, DollarSign, 
  Target, Package, Download, Calendar, Zap, Building2
} from 'lucide-react';
import axios from 'axios';

const AddyOnboarding = ({ user, session: existingSession }) => {
  const [currentPhase, setCurrentPhase] = useState(existingSession?.current_phase || 'arrival');
  const [sessionData, setSessionData] = useState(existingSession?.data || {
    business_description: '',
    detected_category: '',
    confirmed_category: '',
    priorities: [],
    team_size: '',
    income_pattern: '',
  });
  const [isTyping, setIsTyping] = useState(false);
  const [showOptions, setShowOptions] = useState(false);

  // Simulate typing effect for bot messages
  const showMessageWithDelay = (delay = 1000) => {
    setIsTyping(true);
    setShowOptions(false);
    setTimeout(() => {
      setIsTyping(false);
      setTimeout(() => setShowOptions(true), 300);
    }, delay);
  };

  useEffect(() => {
    showMessageWithDelay(800);
  }, [currentPhase]);

  // Phase configurations
  const phases = {
    arrival: {
      icon: Sparkles,
      title: "Welcome to Addy",
      message: "Welcome. I'm Addy, your business clarity system. I help you manage operations, finances, teams, and decisions in one place. Let's set up your workspace.",
      question: "Ready to begin?",
      options: [
        { label: "Yes, let's begin", value: 'yes', primary: true },
        { label: "Not now", value: 'no', secondary: true },
      ],
    },
    business_description: {
      icon: Building2,
      title: "Tell us about your business",
      message: "Let's start with the basics.",
      question: "Tell me in one sentence what your business does.",
      inputType: 'text',
      placeholder: 'e.g., We sell fresh produce to local restaurants',
    },
    business_classification: {
      icon: Target,
      title: "Business Classification",
      message: `Based on what you said, it sounds like your business is in`,
      question: "Does that look right?",
      options: [
        { label: "Yes, that's right", value: 'yes', primary: true },
        { label: "No, let me choose", value: 'no' },
      ],
    },
    business_category_select: {
      icon: Target,
      title: "Choose Your Category",
      message: "No problem – choose the option that fits you best.",
      options: [
        { label: "Retail / Shop / Store", value: 'Retail / Shop / Store', icon: '🏪' },
        { label: "Services & Consulting", value: 'Services & Consulting', icon: '💼' },
        { label: "Beauty & Personal Care", value: 'Beauty & Personal Care', icon: '💇' },
        { label: "Construction & Engineering", value: 'Construction & Engineering', icon: '🏗️' },
        { label: "Agriculture & Farming", value: 'Agriculture & Farming', icon: '🌾' },
        { label: "Education / Training", value: 'Education / Training', icon: '📚' },
        { label: "Health & Medical", value: 'Health & Medical', icon: '⚕️' },
        { label: "Hospitality & Food", value: 'Hospitality & Food', icon: '🍽️' },
        { label: "Manufacturing & Production", value: 'Manufacturing & Production', icon: '🏭' },
        { label: "Transport & Logistics", value: 'Transport & Logistics', icon: '🚚' },
        { label: "NGO / Community Work", value: 'NGO / Community Work', icon: '🤝' },
        { label: "Finance / Insurance", value: 'Finance / Insurance', icon: '💰' },
        { label: "Technology & Software", value: 'Technology & Software', icon: '💻' },
        { label: "Creative / Media", value: 'Creative / Media', icon: '🎨' },
        { label: "Other", value: 'Other', icon: '📋' },
      ],
    },
    priorities: {
      icon: Target,
      title: "What matters most?",
      message: "What do you want Addy to help you with first?",
      multiSelect: true,
      options: [
        { label: "Finance & budgets", value: 'finance', icon: '💰' },
        { label: "Sales & quotations", value: 'sales', icon: '📊' },
        { label: "Projects & tasks", value: 'projects', icon: '✅' },
        { label: "Team & HR", value: 'team', icon: '👥' },
        { label: "Inventory & suppliers", value: 'inventory', icon: '📦' },
        { label: "Approvals & workflows", value: 'approvals', icon: '✓' },
        { label: "Everything", value: 'everything', icon: '⭐' },
      ],
    },
    team_size: {
      icon: Users,
      title: "Your Team",
      message: "How many people are involved in running this business?",
      options: [
        { label: "Just me", value: 'solo', icon: '👤' },
        { label: "2–5 people", value: '2-5', icon: '👥' },
        { label: "6–20 people", value: '6-20', icon: '👥👥' },
        { label: "20+ people", value: '20+', icon: '👥👥👥' },
        { label: "It depends", value: 'varies', icon: '🤷' },
      ],
    },
    financial_rhythm: {
      icon: Calendar,
      title: "Financial Rhythm",
      message: "Which of these describes your income pattern best?",
      options: [
        { label: "Predictable monthly", value: 'monthly', icon: '📅' },
        { label: "Seasonal", value: 'seasonal', icon: '🌤️' },
        { label: "Contract-based", value: 'contract', icon: '📝' },
        { label: "Volatile", value: 'volatile', icon: '📈' },
        { label: "Startup mode", value: 'startup', icon: '🚀' },
      ],
    },
    summary: {
      icon: Check,
      title: "Your Setup",
      message: "Here's your setup so far:",
      showSummary: true,
    },
    complete: {
      icon: Zap,
      title: "You're All Set!",
      message: "Your business workspace is ready. From here, I'll help you operate with clarity and control.",
      question: "What do you want to do first?",
      options: [
        { label: "Set budgets", value: 'budgets', icon: '💰' },
        { label: "Add products/services", value: 'products', icon: '📦' },
        { label: "Add team members", value: 'team', icon: '👥' },
        { label: "Upload data", value: 'upload', icon: '📤' },
        { label: "Open dashboard", value: 'dashboard', primary: true, icon: '🎯' },
      ],
    },
  };

  const currentPhaseData = phases[currentPhase];
  const Icon = currentPhaseData?.icon;

  // Handle user response
  const handleResponse = async (value) => {
    // Save response
    const updates = { ...sessionData };

    switch (currentPhase) {
      case 'arrival':
        if (value === 'yes') {
          await saveProgress('business_description', updates);
          setCurrentPhase('business_description');
        } else {
          router.visit('/dashboard');
        }
        break;

      case 'business_description':
        updates.business_description = value;
        setSessionData(updates);
        
        // Call AI to classify
        try {
          const response = await axios.post('/api/onboarding/classify', { description: value });
          updates.detected_category = response.data.category;
          setSessionData(updates);
          await saveProgress('business_classification', updates);
          setCurrentPhase('business_classification');
        } catch (error) {
          console.error('Classification error:', error);
          updates.detected_category = 'General Business';
          setSessionData(updates);
          await saveProgress('business_classification', updates);
          setCurrentPhase('business_classification');
        }
        break;

      case 'business_classification':
        if (value === 'yes') {
          updates.confirmed_category = updates.detected_category;
          setSessionData(updates);
          await saveProgress('priorities', updates);
          setCurrentPhase('priorities');
        } else {
          setCurrentPhase('business_category_select');
        }
        break;

      case 'business_category_select':
        updates.confirmed_category = value;
        setSessionData(updates);
        await saveProgress('priorities', updates);
        setCurrentPhase('priorities');
        break;

      case 'priorities':
        updates.priorities = Array.isArray(value) ? value : [value];
        setSessionData(updates);
        await saveProgress('team_size', updates);
        setCurrentPhase('team_size');
        break;

      case 'team_size':
        updates.team_size = value;
        setSessionData(updates);
        await saveProgress('financial_rhythm', updates);
        setCurrentPhase('financial_rhythm');
        break;

      case 'financial_rhythm':
        updates.income_pattern = value;
        setSessionData(updates);
        await saveProgress('summary', updates);
        setCurrentPhase('summary');
        break;

      case 'summary':
        // Complete onboarding
        await completeOnboarding(updates);
        setCurrentPhase('complete');
        break;

      case 'complete':
        // Redirect to chosen action
        const redirectUrl = getRedirectUrl(value);
        if (redirectUrl) {
          router.visit(redirectUrl);
        }
        break;
    }
  };

  // Save progress
  const saveProgress = async (phase, data) => {
    try {
      await axios.post('/api/onboarding/save-progress', {
        phase,
        data,
      });
    } catch (error) {
      console.error('Failed to save progress:', error);
    }
  };

  // Complete onboarding
  const completeOnboarding = async (data) => {
    try {
      const response = await axios.post('/onboarding/complete', data);
      if (response.data.success && response.data.redirect) {
        // Redirect will be handled by the response
        setTimeout(() => {
          window.location.href = response.data.redirect;
        }, 2000);
      }
    } catch (error) {
      console.error('Failed to complete onboarding:', error);
      alert('There was an error completing onboarding. Please try again.');
    }
  };

  const getRedirectUrl = (action) => {
    const urls = {
      dashboard: '/dashboard',
      budgets: '/money/budgets',
      products: '/products',
      team: '/people',
      upload: '/data-upload',
    };
    return urls[action] || '/dashboard';
  };

  return (
    <>
      <Head title="Welcome to Addy" />
      <div className="min-h-screen bg-gradient-to-br from-teal-50 via-green-50 to-white flex items-center justify-center p-4 overflow-hidden relative">
        {/* Animated background elements */}
        <div className="absolute inset-0 overflow-hidden pointer-events-none">
          <motion.div
            className="absolute top-20 left-20 w-64 h-64 bg-teal-400/10 rounded-full blur-3xl"
            animate={{
              scale: [1, 1.2, 1],
              x: [0, 30, 0],
              y: [0, -30, 0],
            }}
            transition={{ duration: 8, repeat: Infinity }}
          />
          <motion.div
            className="absolute bottom-20 right-20 w-96 h-96 bg-green-400/10 rounded-full blur-3xl"
            animate={{
              scale: [1, 1.3, 1],
              x: [0, -30, 0],
              y: [0, 30, 0],
            }}
            transition={{ duration: 10, repeat: Infinity }}
          />
        </div>

        {/* Main content */}
        <motion.div
          className="w-full max-w-2xl relative z-10"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6 }}
        >
          {/* Card */}
          <div className="bg-white/70 backdrop-blur-lg border border-white/20 rounded-3xl shadow-2xl p-8 md:p-12">
            <AnimatePresence mode="wait">
              <motion.div
                key={currentPhase}
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: -20 }}
                transition={{ duration: 0.4 }}
              >
                {/* Icon */}
                <motion.div
                  className="w-16 h-16 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center shadow-lg"
                  initial={{ scale: 0, rotate: -180 }}
                  animate={{ scale: 1, rotate: 0 }}
                  transition={{ 
                    type: "spring",
                    stiffness: 200,
                    damping: 15,
                    delay: 0.2 
                  }}
                >
                  {Icon && <Icon size={32} className="text-white" />}
                </motion.div>

                {/* Title */}
                <motion.h1
                  className="text-3xl md:text-4xl font-bold text-gray-900 text-center mb-4"
                  initial={{ opacity: 0, y: 10 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ delay: 0.3 }}
                >
                  {currentPhaseData?.title}
                </motion.h1>

                {/* Message */}
                <motion.div
                  className="text-lg text-gray-700 text-center mb-8 space-y-3"
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  transition={{ delay: 0.4 }}
                >
                  <p>{currentPhaseData?.message}</p>
                  
                  {currentPhase === 'business_classification' && sessionData.detected_category && (
                    <motion.p
                      className="text-2xl font-semibold text-teal-600"
                      initial={{ scale: 0.8, opacity: 0 }}
                      animate={{ scale: 1, opacity: 1 }}
                      transition={{ delay: 0.6, type: "spring" }}
                    >
                      {sessionData.detected_category}
                    </motion.p>
                  )}

                  {currentPhaseData?.question && (
                    <p className="font-medium text-gray-900 mt-6">
                      {currentPhaseData.question}
                    </p>
                  )}
                </motion.div>

                {/* Typing indicator */}
                {isTyping && (
                  <motion.div
                    className="flex items-center justify-center gap-2 mb-8"
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                  >
                    <motion.div
                      className="w-2 h-2 bg-teal-500 rounded-full"
                      animate={{ scale: [1, 1.5, 1] }}
                      transition={{ duration: 0.6, repeat: Infinity }}
                    />
                    <motion.div
                      className="w-2 h-2 bg-teal-500 rounded-full"
                      animate={{ scale: [1, 1.5, 1] }}
                      transition={{ duration: 0.6, repeat: Infinity, delay: 0.2 }}
                    />
                    <motion.div
                      className="w-2 h-2 bg-teal-500 rounded-full"
                      animate={{ scale: [1, 1.5, 1] }}
                      transition={{ duration: 0.6, repeat: Infinity, delay: 0.4 }}
                    />
                  </motion.div>
                )}

                {/* Input for text responses */}
                {showOptions && currentPhaseData?.inputType === 'text' && (
                  <TextInput
                    placeholder={currentPhaseData.placeholder}
                    onSubmit={handleResponse}
                  />
                )}

                {/* Options */}
                {showOptions && currentPhaseData?.options && (
                  <OptionsGrid
                    options={currentPhaseData.options}
                    multiSelect={currentPhaseData.multiSelect}
                    onSelect={handleResponse}
                  />
                )}

                {/* Summary view */}
                {showOptions && currentPhaseData?.showSummary && (
                  <SummaryView
                    data={sessionData}
                    onContinue={() => handleResponse('continue')}
                  />
                )}
              </motion.div>
            </AnimatePresence>
          </div>

          {/* Progress indicator */}
          <ProgressIndicator phase={currentPhase} />
        </motion.div>
      </div>
    </>
  );
};

// Text Input Component
const TextInput = ({ placeholder, onSubmit }) => {
  const [value, setValue] = useState('');

  const handleSubmit = (e) => {
    e.preventDefault();
    if (value.trim()) {
      onSubmit(value.trim());
      setValue('');
    }
  };

  return (
    <motion.form
      onSubmit={handleSubmit}
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: 0.5 }}
    >
      <div className="relative">
        <input
          type="text"
          value={value}
          onChange={(e) => setValue(e.target.value)}
          placeholder={placeholder}
          className="w-full px-6 py-4 text-lg border-2 border-gray-300 rounded-xl focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20 transition-all"
          autoFocus
        />
        <button
          type="submit"
          className="absolute right-2 top-1/2 -translate-y-1/2 p-2 bg-teal-500 text-white rounded-lg hover:bg-teal-600 transition-colors disabled:opacity-50"
          disabled={!value.trim()}
        >
          <ArrowRight size={24} />
        </button>
      </div>
    </motion.form>
  );
};

// Options Grid Component
const OptionsGrid = ({ options, multiSelect, onSelect }) => {
  const [selected, setSelected] = useState(multiSelect ? [] : null);

  const handleClick = (value) => {
    if (multiSelect) {
      const newSelected = selected.includes(value)
        ? selected.filter(v => v !== value)
        : [...selected, value];
      setSelected(newSelected);
    } else {
      onSelect(value);
    }
  };

  const handleContinue = () => {
    if (selected.length > 0) {
      onSelect(selected);
    }
  };

  return (
    <motion.div
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      transition={{ delay: 0.5 }}
    >
      <div className="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6">
        {options.map((option, index) => {
          const isSelected = multiSelect 
            ? selected.includes(option.value)
            : false;

          return (
            <motion.button
              key={option.value}
              onClick={() => handleClick(option.value)}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.5 + index * 0.05 }}
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              className={`
                p-4 rounded-xl border-2 transition-all text-left
                ${option.primary 
                  ? 'bg-teal-500 text-white border-teal-600 hover:bg-teal-600' 
                  : isSelected
                  ? 'bg-teal-50 border-teal-500 text-teal-700'
                  : 'bg-white/50 border-gray-300 hover:border-teal-400 text-gray-700'
                }
              `}
            >
              <div className="flex items-center gap-3">
                {option.icon && (
                  <span className="text-2xl">{option.icon}</span>
                )}
                <span className="font-medium">{option.label}</span>
                {multiSelect && isSelected && (
                  <Check size={20} className="ml-auto" />
                )}
              </div>
            </motion.button>
          );
        })}
      </div>

      {multiSelect && selected.length > 0 && (
        <motion.button
          onClick={handleContinue}
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          whileHover={{ scale: 1.02 }}
          whileTap={{ scale: 0.98 }}
          className="w-full py-4 bg-teal-500 text-white rounded-xl hover:bg-teal-600 transition-all font-semibold flex items-center justify-center gap-2"
        >
          Continue with {selected.length} selected
          <ArrowRight size={20} />
        </motion.button>
      )}
    </motion.div>
  );
};

// Summary View Component
const SummaryView = ({ data, onContinue }) => {
  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      className="space-y-4"
    >
      <div className="bg-white/50 backdrop-blur-sm p-6 rounded-xl space-y-3 border border-gray-200">
        <SummaryItem label="Business" value={data.business_description} />
        <SummaryItem label="Category" value={data.confirmed_category} />
        <SummaryItem label="Focus" value={Array.isArray(data.priorities) ? data.priorities.join(', ') : data.priorities} />
        <SummaryItem label="Team" value={data.team_size} />
        <SummaryItem label="Income Pattern" value={data.income_pattern} />
      </div>

      <motion.button
        onClick={onContinue}
        whileHover={{ scale: 1.02 }}
        whileTap={{ scale: 0.98 }}
        className="w-full py-4 bg-teal-500 text-white rounded-xl hover:bg-teal-600 transition-all font-semibold flex items-center justify-center gap-2"
      >
        Looks good! Continue
        <ArrowRight size={20} />
      </motion.button>
    </motion.div>
  );
};

const SummaryItem = ({ label, value }) => (
  <div className="flex justify-between items-center py-2 border-b border-gray-200 last:border-0">
    <span className="text-gray-600">{label}:</span>
    <span className="font-semibold text-gray-900">{value || '—'}</span>
  </div>
);

// Progress Indicator
const ProgressIndicator = ({ phase }) => {
  const phases = ['arrival', 'business_description', 'business_classification', 'priorities', 'team_size', 'financial_rhythm', 'summary', 'complete'];
  const currentIndex = phases.indexOf(phase);
  const progress = ((currentIndex + 1) / phases.length) * 100;

  return (
    <motion.div 
      className="mt-8 mx-auto max-w-md"
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      transition={{ delay: 0.8 }}
    >
      <div className="h-2 bg-white/50 rounded-full overflow-hidden">
        <motion.div
          className="h-full bg-gradient-to-r from-teal-500 to-green-500"
          initial={{ width: 0 }}
          animate={{ width: `${progress}%` }}
          transition={{ duration: 0.5, ease: "easeOut" }}
        />
      </div>
      <p className="text-center text-sm text-gray-600 mt-2">
        Step {currentIndex + 1} of {phases.length}
      </p>
    </motion.div>
  );
};

export default AddyOnboarding;

