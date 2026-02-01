import React, { useState } from 'react';
import { 
    HelpCircle, 
    CheckCircle, 
    AlertCircle, 
    ChevronRight,
    X,
    Loader2
} from 'lucide-react';

/**
 * Inline OCR Review Component for Chat
 * A compact question-by-question review interface embedded in chat messages
 */
export default function InlineChatOcrReview({ 
    ocrResult, 
    onSubmit, 
    onSkip,
    isSubmitting = false 
}) {
    const [answers, setAnswers] = useState({});
    const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
    
    const { data, questions = [], document_type, confidence } = ocrResult;
    
    // If no questions, show ready to import
    if (!questions || questions.length === 0) {
        return (
            <div className="bg-green-50 border border-green-200 rounded-xl p-4 mt-3">
                <div className="flex items-center gap-3">
                    <CheckCircle className="w-5 h-5 text-green-600" />
                    <div className="flex-1">
                        <p className="font-medium text-green-800">All data looks good!</p>
                        <p className="text-sm text-green-600">Ready to import</p>
                    </div>
                    <button
                        onClick={() => onSubmit({ ...data, reviewed: true })}
                        disabled={isSubmitting}
                        className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 text-sm font-medium"
                    >
                        {isSubmitting ? <Loader2 className="w-4 h-4 animate-spin" /> : 'Import Now'}
                    </button>
                </div>
            </div>
        );
    }

    const currentQuestion = questions[currentQuestionIndex];
    const isLastQuestion = currentQuestionIndex === questions.length - 1;
    const answeredCount = Object.keys(answers).length;
    const progress = ((answeredCount) / questions.length) * 100;

    // Handle answer for current question
    const handleAnswer = (value) => {
        const newAnswers = {
            ...answers,
            [currentQuestion.field]: value,
        };
        setAnswers(newAnswers);

        // Auto-advance to next question after short delay
        if (!isLastQuestion) {
            setTimeout(() => {
                setCurrentQuestionIndex(currentQuestionIndex + 1);
            }, 200);
        }
    };

    // Submit all answers
    const handleSubmit = () => {
        const finalData = {
            ...data,
            ...answers,
            reviewed: true,
            review_timestamp: new Date().toISOString(),
        };
        onSubmit(finalData);
    };

    // Go to previous question
    const handlePrevious = () => {
        if (currentQuestionIndex > 0) {
            setCurrentQuestionIndex(currentQuestionIndex - 1);
        }
    };

    // Go to next question
    const handleNext = () => {
        if (!isLastQuestion) {
            setCurrentQuestionIndex(currentQuestionIndex + 1);
        }
    };

    return (
        <div className="bg-amber-50 border border-amber-200 rounded-xl overflow-hidden mt-3">
            {/* Header */}
            <div className="flex items-center justify-between p-3 bg-amber-100/50 border-b border-amber-200">
                <div className="flex items-center gap-2">
                    <AlertCircle className="w-4 h-4 text-amber-600" />
                    <span className="text-sm font-medium text-amber-800">
                        Review Required ({answeredCount}/{questions.length})
                    </span>
                </div>
                <button
                    onClick={onSkip}
                    className="text-amber-600 hover:text-amber-700 text-sm"
                >
                    Skip
                </button>
            </div>

            {/* Progress Bar */}
            <div className="h-1 bg-amber-200">
                <div 
                    className="h-full bg-amber-500 transition-all duration-300"
                    style={{ width: `${progress}%` }}
                />
            </div>

            {/* Question Card */}
            {currentQuestion && (
                <div className="p-4">
                    {/* Question */}
                    <div className="flex items-start gap-3 mb-4">
                        <div className="p-1.5 bg-amber-100 rounded-lg text-amber-600">
                            <HelpCircle className="w-4 h-4" />
                        </div>
                        <div className="flex-1">
                            <p className="text-sm font-medium text-gray-900 mb-1">
                                {currentQuestion.question}
                            </p>
                            {currentQuestion.reason && (
                                <p className="text-xs text-amber-600">
                                    {currentQuestion.reason}
                                </p>
                            )}
                        </div>
                    </div>

                    {/* Current Value Badge */}
                    {currentQuestion.current_value && (
                        <div className="mb-3 px-2 py-1.5 bg-white border border-gray-200 rounded-lg inline-block text-sm">
                            <span className="text-gray-500">Extracted: </span>
                            <span className="font-medium text-gray-900">{currentQuestion.current_value}</span>
                        </div>
                    )}

                    {/* Input based on question type */}
                    <div className="space-y-2">
                        {currentQuestion.type === 'text_input' && (
                            <input
                                type="text"
                                defaultValue={answers[currentQuestion.field] ?? currentQuestion.current_value ?? ''}
                                onChange={(e) => setAnswers({ ...answers, [currentQuestion.field]: e.target.value })}
                                onBlur={(e) => handleAnswer(e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent text-sm"
                                placeholder={currentQuestion.placeholder || 'Enter value...'}
                            />
                        )}

                        {currentQuestion.type === 'number_input' && (
                            <div className="relative">
                                <span className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">
                                    ZMW
                                </span>
                                <input
                                    type="number"
                                    step="0.01"
                                    defaultValue={answers[currentQuestion.field] ?? currentQuestion.current_value ?? ''}
                                    onChange={(e) => setAnswers({ ...answers, [currentQuestion.field]: e.target.value })}
                                    onBlur={(e) => handleAnswer(e.target.value)}
                                    className="w-full pl-12 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent text-sm"
                                    placeholder="0.00"
                                />
                            </div>
                        )}

                        {currentQuestion.type === 'date_picker' && (
                            <input
                                type="date"
                                defaultValue={answers[currentQuestion.field] ?? currentQuestion.current_value ?? ''}
                                onChange={(e) => handleAnswer(e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent text-sm"
                            />
                        )}

                        {currentQuestion.type === 'select' && currentQuestion.options && (
                            <div className="grid grid-cols-2 gap-2">
                                {currentQuestion.options.map((option) => (
                                    <button
                                        key={option.value}
                                        onClick={() => handleAnswer(option.value)}
                                        className={`px-3 py-2 text-left border rounded-lg text-sm transition-all ${
                                            answers[currentQuestion.field] === option.value
                                                ? 'border-amber-500 bg-amber-50 text-amber-800'
                                                : 'border-gray-200 hover:border-gray-300 text-gray-700'
                                        }`}
                                    >
                                        {option.label}
                                    </button>
                                ))}
                            </div>
                        )}

                        {currentQuestion.type === 'text_with_suggestions' && (
                            <div className="space-y-2">
                                <input
                                    type="text"
                                    defaultValue={answers[currentQuestion.field] ?? currentQuestion.current_value ?? ''}
                                    onChange={(e) => setAnswers({ ...answers, [currentQuestion.field]: e.target.value })}
                                    onBlur={(e) => handleAnswer(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent text-sm"
                                    placeholder="Type or select below..."
                                />
                                {currentQuestion.suggestions && currentQuestion.suggestions.length > 0 && (
                                    <div className="flex flex-wrap gap-1">
                                        {currentQuestion.suggestions.slice(0, 4).map((suggestion, idx) => (
                                            <button
                                                key={idx}
                                                onClick={() => handleAnswer(suggestion.value)}
                                                className="px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded text-xs transition-colors"
                                            >
                                                {suggestion.label}
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>
                        )}

                        {currentQuestion.type === 'confirmation' && (
                            <div className="flex gap-2">
                                <button
                                    onClick={() => handleAnswer(true)}
                                    className={`flex-1 px-3 py-2 rounded-lg text-sm font-medium transition-all ${
                                        answers[currentQuestion.field] === true
                                            ? 'bg-green-600 text-white'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    }`}
                                >
                                    Yes
                                </button>
                                <button
                                    onClick={() => handleAnswer(false)}
                                    className={`flex-1 px-3 py-2 rounded-lg text-sm font-medium transition-all ${
                                        answers[currentQuestion.field] === false
                                            ? 'bg-red-600 text-white'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    }`}
                                >
                                    No
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            )}

            {/* Navigation */}
            <div className="flex items-center justify-between p-3 bg-amber-50 border-t border-amber-200">
                <button
                    onClick={handlePrevious}
                    disabled={currentQuestionIndex === 0}
                    className="text-sm text-amber-600 hover:text-amber-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    ← Previous
                </button>

                {/* Question dots */}
                <div className="flex gap-1">
                    {questions.map((q, idx) => (
                        <button
                            key={idx}
                            onClick={() => setCurrentQuestionIndex(idx)}
                            className={`w-2 h-2 rounded-full transition-all ${
                                idx === currentQuestionIndex
                                    ? 'bg-amber-600 w-4'
                                    : answers[q.field] !== undefined
                                    ? 'bg-green-500'
                                    : 'bg-amber-300'
                            }`}
                        />
                    ))}
                </div>

                {!isLastQuestion ? (
                    <button
                        onClick={handleNext}
                        className="text-sm text-amber-600 hover:text-amber-700 flex items-center gap-1"
                    >
                        Next <ChevronRight className="w-3 h-3" />
                    </button>
                ) : (
                    <button
                        onClick={handleSubmit}
                        disabled={isSubmitting || answeredCount < questions.length}
                        className="flex items-center gap-1 px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 text-sm font-medium"
                    >
                        {isSubmitting ? (
                            <Loader2 className="w-3 h-3 animate-spin" />
                        ) : (
                            <CheckCircle className="w-3 h-3" />
                        )}
                        Submit & Import
                    </button>
                )}
            </div>
        </div>
    );
}
