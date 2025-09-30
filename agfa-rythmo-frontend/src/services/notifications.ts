// Simple notification system
export interface Notification {
  id: string;
  type: 'success' | 'error' | 'info' | 'warning';
  title: string;
  message: string;
  duration?: number;
}

class NotificationService {
  private notifications: Notification[] = [];
  private listeners: ((notifications: Notification[]) => void)[] = [];

  subscribe(callback: (notifications: Notification[]) => void) {
    this.listeners.push(callback);
    callback(this.notifications);

    return () => {
      const index = this.listeners.indexOf(callback);
      if (index > -1) {
        this.listeners.splice(index, 1);
      }
    };
  }

  private notify() {
    this.listeners.forEach(listener => listener([...this.notifications]));
  }

  add(notification: Omit<Notification, 'id'>) {
    const id = Date.now().toString();
    const newNotification: Notification = {
      ...notification,
      id,
      duration: notification.duration ?? 5000
    };

    this.notifications.push(newNotification);
    this.notify();

    if (newNotification.duration && newNotification.duration > 0) {
      setTimeout(() => {
        this.remove(id);
      }, newNotification.duration);
    }

    return id;
  }

  remove(id: string) {
    const index = this.notifications.findIndex(n => n.id === id);
    if (index > -1) {
      this.notifications.splice(index, 1);
      this.notify();
    }
  }

  success(title: string, message: string, duration?: number) {
    return this.add({ type: 'success', title, message, duration });
  }

  error(title: string, message: string, duration?: number) {
    return this.add({ type: 'error', title, message, duration });
  }

  info(title: string, message: string, duration?: number) {
    return this.add({ type: 'info', title, message, duration });
  }

  warning(title: string, message: string, duration?: number) {
    return this.add({ type: 'warning', title, message, duration });
  }
}

export const notificationService = new NotificationService();
