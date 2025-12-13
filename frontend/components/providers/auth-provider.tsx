'use client';

import { createContext, useContext, ReactNode } from 'react';

interface AuthContextType {
  isLoggedIn: boolean;
}

const AuthContext = createContext<AuthContextType | null>(null);

export function AuthProvider({
  children,
  initialIsLoggedIn,
}: {
  children: ReactNode;
  initialIsLoggedIn: boolean;
}) {
  return (
    <AuthContext.Provider value={{ isLoggedIn: initialIsLoggedIn }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}