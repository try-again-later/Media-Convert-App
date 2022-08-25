export type Status = 'waiting' | 'retrying' | 'success' | 'first-try';

export type Workload<T> = () => Promise<T>;
