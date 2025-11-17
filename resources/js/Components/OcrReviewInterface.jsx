import React, { useState } from 'react';
import { HelpCircle, CheckCircle, AlertCircle, Calendar, DollarSign, User, Tag } from 'lucide-react';

const OcrReviewInterface = ({ ocrResult, onSubmit, onCancel }) => {
  const [answers, setAnswers] = useState({});
  const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
  
  const { data, questions = [], uncertainty_analysis, document_type } = ocrResult;
  const currentQuestion = questions[currentQuestionIndex];
  const isLastQuestion = currentQuestionIndex === questions.length - 1;

  // Handle answer for current question
  const handleAnswer = (value) => {
    setAnswers({
      ...answers,
      [currentQuestion.field]: value,
    });

    // Auto-advance to next question
    if (!isLastQuestion) {
      setTimeout(() => {
        setCurrentQuestionIndex(currentQuestionIndex + 1);
      }, 300);
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

  // Skip to specific question
  const jumpToQuestion = (index) => {
    setCurrentQuestionIndex(index);
  };

  if (!currentQuestion) {
    return (
      <div className="max-w-4xl mx-auto p-6">
        <div className="bg-white rounded-xl border border-gray-200 p-8 text-center">
          <CheckCircle className="w-16 h-16 text-green-500 mx-auto mb-4" />
          <h3 className="text-xl font-semibold text-gray-900 mb-2">No Questions</h3>
          <p className="text-gray-600 mb-6">All data looks good! Ready to import.</p>
          <button
            onClick={() => onSubmit({ ...data, reviewed: true })}
            className="px-6 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-medium"
          >
            Import Now
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto">
      {/* Header */}
      <div className="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <div className="flex items-start justify-between">
          <div>
            <h2 className="text-2xl font-bold text-gray-900 mb-2">
              Review OCR Results
            </h2>
            <p className="text-gray-600">
              I found some information that needs your confirmation
            </p>
          </div>
          <div className="text-right">
            <div className="text-sm text-gray-500 mb-1">Document Type</div>
            <div className="px-3 py-1 bg-purple-100 text-purple-700 rounded-lg text-sm font-medium capitalize">
              {document_type}
            </div>
          </div>
        </div>

        {/* Progress */}
        <div className="mt-6">
          <div className="flex justify-between text-sm text-gray-600 mb-2">
            <span>Question {currentQuestionIndex + 1} of {questions.length}</span>
            <span>{Math.round(((currentQuestionIndex + 1) / questions.length) * 100)}% complete</span>
          </div>
          <div className="w-full bg-gray-200 rounded-full h-2">
            <div
              className="bg-teal-600 h-2 rounded-full transition-all duration-300"
              style={{ width: `${((currentQuestionIndex + 1) / questions.length) * 100}%` }}
            />
          </div>
        </div>
      </div>

      {/* Extracted Data Summary */}
      <div className="bg-gray-50 rounded-xl border border-gray-200 p-4 mb-6">
        <h3 className="text-sm font-semibold text-gray-700 mb-3">What I Found:</h3>
        <div className="grid grid-cols-2 gap-3">
          {Object.entries(data)
            .filter(([key, value]) => value && !['type', 'raw_text', 'items'].includes(key))
            .slice(0, 6)
            .map(([key, value]) => (
              <div key={key} className="text-sm">
                <span className="text-gray-500 capitalize">{key.replace(/_/g, ' ')}:</span>{' '}
                <span className="font-medium text-gray-900">
                  {typeof value === 'object' ? JSON.stringify(value) : value}
                </span>
              </div>
            ))}
        </div>
      </div>

      {/* Current Question */}
      {currentQuestion && (
        <div className="bg-white rounded-xl border-2 border-teal-200 p-8 mb-6">
          {/* Question Icon */}
          <div className="flex items-start gap-4 mb-6">
            <div className="p-3 bg-teal-100 rounded-xl">
              <HelpCircle className="text-teal-600" size={28} />
            </div>
            <div className="flex-1">
              <h3 className="text-xl font-semibold text-gray-900 mb-2">
                {currentQuestion.question}
              </h3>
              {currentQuestion.reason && (
                <p className="text-sm text-gray-600 flex items-center gap-2">
                  <AlertCircle size={16} className="text-amber-500" />
                  {currentQuestion.reason}
                </p>
              )}
            </div>
          </div>

          {/* Answer Input */}
          <div className="space-y-4">
            {/* Current Value Display */}
            {currentQuestion.current_value && (
              <div className="bg-gray-50 rounded-lg p-3 border border-gray-200">
                <div className="text-xs text-gray-500 mb-1">I extracted:</div>
                <div className="font-medium text-gray-900">
                  {currentQuestion.current_value}
                </div>
              </div>
            )}

            {/* Input based on question type */}
            {currentQuestion.type === 'text_input' && (
              <input
                type="text"
                defaultValue={currentQuestion.current_value || ''}
                onChange={(e) => handleAnswer(e.target.value)}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                placeholder={currentQuestion.placeholder || 'Enter value...'}
                autoFocus
              />
            )}

            {currentQuestion.type === 'number_input' && (
              <div className="relative">
                <div className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">
                  ZMW
                </div>
                <input
                  type="number"
                  step="0.01"
                  defaultValue={currentQuestion.current_value || ''}
                  onChange={(e) => handleAnswer(e.target.value)}
                  className="w-full pl-16 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                  placeholder="0.00"
                  autoFocus
                />
              </div>
            )}

            {currentQuestion.type === 'date_picker' && (
              <input
                type="date"
                defaultValue={currentQuestion.current_value || ''}
                onChange={(e) => handleAnswer(e.target.value)}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                autoFocus
              />
            )}

            {currentQuestion.type === 'select' && (
              <div className="space-y-2">
                {currentQuestion.options.map((option) => (
                  <button
                    key={option.value}
                    onClick={() => handleAnswer(option.value)}
                    className={`w-full px-4 py-3 text-left border-2 rounded-lg transition-all ${
                      answers[currentQuestion.field] === option.value
                        ? 'border-teal-600 bg-teal-50'
                        : 'border-gray-200 hover:border-gray-300'
                    }`}
                  >
                    <div className="flex items-center justify-between">
                      <span className="font-medium text-gray-900">{option.label}</span>
                      {answers[currentQuestion.field] === option.value && (
                        <CheckCircle size={20} className="text-teal-600" />
                      )}
                    </div>
                  </button>
                ))}
              </div>
            )}

            {currentQuestion.type === 'text_with_suggestions' && (
              <div>
                <input
                  type="text"
                  defaultValue={currentQuestion.current_value || ''}
                  onChange={(e) => handleAnswer(e.target.value)}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent mb-3"
                  placeholder="Type or select below..."
                  autoFocus
                />
                {currentQuestion.suggestions && currentQuestion.suggestions.length > 0 && (
                  <div className="space-y-2">
                    <div className="text-xs text-gray-500 font-medium">Suggestions:</div>
                    <div className="flex flex-wrap gap-2">
                      {currentQuestion.suggestions.map((suggestion, idx) => (
                        <button
                          key={idx}
                          onClick={() => handleAnswer(suggestion.value)}
                          className="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm transition-colors"
                        >
                          {suggestion.label}
                        </button>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            )}

            {currentQuestion.type === 'confirmation' && (
              <div className="flex gap-3">
                <button
                  onClick={() => handleAnswer(true)}
                  className={`flex-1 px-6 py-3 rounded-lg font-medium transition-all ${
                    answers[currentQuestion.field] === true
                      ? 'bg-green-600 text-white'
                      : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                  }`}
                >
                  Yes
                </button>
                <button
                  onClick={() => handleAnswer(false)}
                  className={`flex-1 px-6 py-3 rounded-lg font-medium transition-all ${
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

          {/* Navigation */}
          <div className="flex justify-between items-center mt-6 pt-6 border-t border-gray-200">
            <button
              onClick={handlePrevious}
              disabled={currentQuestionIndex === 0}
              className="px-4 py-2 text-gray-600 hover:text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              ← Previous
            </button>

            {!isLastQuestion ? (
              <button
                onClick={() => setCurrentQuestionIndex(currentQuestionIndex + 1)}
                disabled={!answers[currentQuestion.field]}
                className="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Next →
              </button>
            ) : (
              <button
                onClick={handleSubmit}
                disabled={!answers[currentQuestion.field]}
                className="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
              >
                <CheckCircle size={20} />
                Complete Review
              </button>
            )}
          </div>
        </div>
      )}

      {/* Question Navigator */}
      <div className="bg-white rounded-xl border border-gray-200 p-4">
        <div className="text-sm text-gray-600 mb-3">Questions:</div>
        <div className="flex flex-wrap gap-2">
          {questions.map((q, idx) => (
            <button
              key={idx}
              onClick={() => jumpToQuestion(idx)}
              className={`w-10 h-10 rounded-lg font-medium transition-all ${
                idx === currentQuestionIndex
                  ? 'bg-teal-600 text-white'
                  : answers[q.field]
                  ? 'bg-green-100 text-green-700'
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
              }`}
            >
              {idx + 1}
            </button>
          ))}
        </div>
      </div>

      {/* Actions */}
      <div className="flex justify-between items-center mt-6">
        <button
          onClick={onCancel}
          className="px-4 py-2 text-gray-600 hover:text-gray-900"
        >
          Cancel Import
        </button>
        
        <div className="text-sm text-gray-500">
          {Object.keys(answers).length} of {questions.length} answered
        </div>
      </div>
    </div>
  );
};

export default OcrReviewInterface;

