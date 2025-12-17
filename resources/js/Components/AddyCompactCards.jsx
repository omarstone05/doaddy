import React from 'react';
import StackedCards from './StackedCards';
import { Star, Trophy, Flame, Bell, Clock, DollarSign, FileText, Users, CheckCircle, XCircle } from 'lucide-react';

/**
 * AddyCompactCards - A compact variant for quick actions
 * Perfect for notifications, quick approvals, deal pipeline, insights, etc.
 */
const AddyCompactCards = ({ items, type = 'notification', onAction }) => {
  
  const handleDismiss = (item, direction) => {
    const action = direction === 'right' ? 'approve' : 'reject';
    
    if (onAction) {
      onAction(item, action);
    }
  };

  const renderNotificationCard = (item) => (
    <div className="w-full h-full bg-white flex flex-col">
      {/* Notification Icon Header */}
      <div className="bg-gradient-to-r from-teal-500 to-teal-600 p-5">
        <div className="flex items-center gap-3">
          <div className="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center text-2xl">
            {item.icon || '🔔'}
          </div>
          <div className="flex-1 text-white">
            <p className="text-xs font-medium opacity-90">{item.timestamp}</p>
            <p className="text-sm font-semibold">{item.from || 'System'}</p>
          </div>
        </div>
      </div>

      {/* Content */}
      <div className="flex-1 p-6 flex flex-col justify-between">
        <div>
          <h3 className="text-xl font-bold text-gray-900 mb-3">{item.title}</h3>
          <p className="text-gray-600 leading-relaxed">{item.message}</p>
        </div>

        {/* Action Hints */}
        <div className="flex items-center justify-between pt-6 border-t border-gray-100 mt-6">
          <div className="flex items-center gap-2 text-red-500">
            <XCircle className="w-5 h-5" />
            <span className="text-sm font-semibold">Dismiss</span>
          </div>
          <div className="flex items-center gap-2 text-teal-500">
            <span className="text-sm font-semibold">Accept</span>
            <CheckCircle className="w-5 h-5" />
          </div>
        </div>
      </div>
    </div>
  );

  const renderInsightCard = (item) => (
    <div className="w-full h-full bg-gradient-to-br from-white to-gray-50 flex flex-col">
      {/* Insight Header */}
      <div className="bg-gradient-to-r from-teal-600 to-teal-500 p-5">
        <div className="flex items-start justify-between mb-2">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
              <Star className="w-5 h-5 text-white" />
            </div>
            <div>
              <p className="text-teal-100 text-xs font-medium">Addy Insight</p>
              <p className="text-white text-sm font-semibold">{item.category || 'Business Tip'}</p>
            </div>
          </div>
          {item.priority === 'high' && (
            <span className="bg-amber-400 text-amber-900 px-2 py-0.5 rounded-full text-xs font-bold">
              Important
            </span>
          )}
        </div>
      </div>

      {/* Content */}
      <div className="flex-1 p-6 flex flex-col">
        <h3 className="text-lg font-bold text-gray-900 mb-3">{item.title}</h3>
        <p className="text-gray-600 leading-relaxed flex-1">{item.message}</p>
        
        {item.action && (
          <div className="mt-4 p-3 bg-teal-50 rounded-xl border border-teal-100">
            <p className="text-sm font-semibold text-teal-700">💡 {item.action}</p>
          </div>
        )}

        {/* Action Hints */}
        <div className="flex items-center justify-between pt-4 border-t border-gray-100 mt-4">
          <div className="flex items-center gap-2 text-gray-400">
            <span className="text-sm font-medium">← Skip for now</span>
          </div>
          <div className="flex items-center gap-2 text-teal-500">
            <span className="text-sm font-semibold">Got it! →</span>
          </div>
        </div>
      </div>
    </div>
  );

  const renderTaskCard = (item) => {
    const priorityColors = {
      high: 'bg-red-500',
      medium: 'bg-amber-500',
      low: 'bg-teal-500',
    };

    return (
      <div className="w-full h-full bg-white flex flex-col">
        {/* Task Header */}
        <div className="bg-gradient-to-r from-teal-600 to-teal-500 p-5 text-white">
          <div className="flex items-center gap-2 mb-3">
            <span className={`${priorityColors[item.priority] || priorityColors.medium} px-3 py-1 rounded-full text-xs font-semibold uppercase`}>
              {item.priority}
            </span>
            {item.category && (
              <span className="bg-teal-700/50 px-3 py-1 rounded-full text-xs font-medium">
                {item.category}
              </span>
            )}
          </div>
          <h3 className="text-xl font-bold">{item.title}</h3>
        </div>

        {/* Task Body */}
        <div className="flex-1 p-6 space-y-4">
          <p className="text-gray-600 leading-relaxed">{item.description}</p>

          <div className="space-y-3 pt-2">
            {item.assignee && (
              <div className="flex items-center gap-3">
                <div className="w-9 h-9 bg-teal-100 rounded-lg flex items-center justify-center">
                  <Users className="w-4 h-4 text-teal-600" />
                </div>
                <div>
                  <p className="text-xs text-gray-500">Assigned to</p>
                  <p className="text-gray-900 font-medium text-sm">{item.assignee}</p>
                </div>
              </div>
            )}

            {item.dueDate && (
              <div className="flex items-center gap-3">
                <div className="w-9 h-9 bg-teal-100 rounded-lg flex items-center justify-center">
                  <Clock className="w-4 h-4 text-teal-600" />
                </div>
                <div>
                  <p className="text-xs text-gray-500">Due date</p>
                  <p className="text-gray-900 font-medium text-sm">{item.dueDate}</p>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Footer */}
        <div className="p-4 bg-gray-50 border-t border-gray-100">
          <div className="flex items-center justify-between text-sm">
            <div className="flex items-center gap-2 text-red-500">
              <XCircle className="w-4 h-4" />
              <span className="font-medium">Postpone</span>
            </div>
            <div className="flex items-center gap-2 text-teal-500">
              <span className="font-medium">Complete</span>
              <CheckCircle className="w-4 h-4" />
            </div>
          </div>
        </div>
      </div>
    );
  };

  const renderApprovalCard = (item) => (
    <div className="w-full h-full bg-white flex flex-col">
      {/* Approval Header */}
      <div className="bg-gradient-to-r from-amber-500 to-orange-500 p-5 text-white">
        <div className="flex items-center gap-3 mb-3">
          <div className="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center text-2xl">
            📋
          </div>
          <div>
            <p className="text-sm opacity-90">Pending Approval</p>
            <h3 className="text-lg font-bold">{item.type}</h3>
          </div>
        </div>
        {item.urgency === 'high' && (
          <span className="bg-red-600 px-3 py-1 rounded-full text-xs font-semibold">
            ⚡ Urgent
          </span>
        )}
      </div>

      {/* Details */}
      <div className="flex-1 p-6 space-y-4">
        <div>
          <h4 className="font-bold text-gray-900 text-lg mb-2">{item.title}</h4>
          <p className="text-gray-600 text-sm">{item.description}</p>
        </div>

        <div className="grid grid-cols-2 gap-3">
          <div className="bg-gray-50 p-3 rounded-xl">
            <p className="text-xs text-gray-500 mb-1">Requested by</p>
            <p className="font-semibold text-gray-900 text-sm">{item.requestedBy}</p>
          </div>
          <div className="bg-gray-50 p-3 rounded-xl">
            <p className="text-xs text-gray-500 mb-1">Amount</p>
            <p className="font-semibold text-gray-900 text-sm">K {item.amount}</p>
          </div>
        </div>
      </div>

      {/* Action Footer */}
      <div className="p-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
        <div className="flex items-center gap-2 text-red-500">
          <XCircle className="w-5 h-5" />
          <span className="text-sm font-bold">REJECT</span>
        </div>
        <div className="flex items-center gap-2 text-teal-500">
          <span className="text-sm font-bold">APPROVE</span>
          <CheckCircle className="w-5 h-5" />
        </div>
      </div>
    </div>
  );

  const renderGamificationCard = (item) => (
    <div className="w-full h-full bg-gradient-to-br from-amber-50 to-yellow-50 flex flex-col">
      {/* Gamification Header */}
      <div className="bg-gradient-to-r from-amber-500 to-yellow-500 p-5">
        <div className="flex items-center gap-3">
          <div className="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
            {item.type === 'badge' ? <Trophy className="w-6 h-6 text-white" /> : 
             item.type === 'streak' ? <Flame className="w-6 h-6 text-white" /> :
             <Star className="w-6 h-6 text-white" />}
          </div>
          <div className="flex-1 text-white">
            <p className="text-xs font-medium opacity-90">Achievement Unlocked!</p>
            <p className="text-lg font-bold">{item.title}</p>
          </div>
        </div>
      </div>

      {/* Content */}
      <div className="flex-1 p-6 flex flex-col justify-between">
        <div className="text-center">
          <div className="text-6xl mb-4">{item.icon}</div>
          <h3 className="text-xl font-bold text-gray-900 mb-2">{item.name}</h3>
          <p className="text-gray-600">{item.message}</p>
          
          {item.xpReward && (
            <div className="mt-4 inline-flex items-center gap-2 bg-amber-100 text-amber-700 px-4 py-2 rounded-full">
              <Star className="w-4 h-4" />
              <span className="font-bold">+{item.xpReward} XP</span>
            </div>
          )}
        </div>

        {/* Action Hints */}
        <div className="flex items-center justify-center pt-4 border-t border-amber-200 mt-4">
          <div className="flex items-center gap-2 text-amber-600">
            <span className="text-sm font-semibold">Swipe to celebrate! 🎉</span>
          </div>
        </div>
      </div>
    </div>
  );

  const renderCard = (item) => {
    switch (type) {
      case 'task':
        return renderTaskCard(item);
      case 'approval':
        return renderApprovalCard(item);
      case 'insight':
        return renderInsightCard(item);
      case 'gamification':
        return renderGamificationCard(item);
      case 'notification':
      default:
        return renderNotificationCard(item);
    }
  };

  return (
    <div className="w-full h-full">
      <StackedCards
        cards={items}
        renderCard={renderCard}
        onDismiss={handleDismiss}
        maxVisible={3}
        cardGap={12}
        scaleStep={0.04}
      />
    </div>
  );
};

export default AddyCompactCards;
