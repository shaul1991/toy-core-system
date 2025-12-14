'use client';

import { createContext, useContext, ReactNode } from 'react';

export interface User {
  email: string;
  name?: string;
  isAdmin?: boolean;
}

interface AuthContextType {
  isLoggedIn: boolean;
  user: User | null;
  isAdmin: boolean;
}

const AuthContext = createContext<AuthContextType | null>(null);

export function AuthProvider({
  children,
  initialIsLoggedIn,
  initialUser,
}: {
  children: ReactNode;
  initialIsLoggedIn: boolean;
  initialUser?: User | null;
}) {
  const isAdmin = initialUser?.isAdmin ?? false;

  return (
    <AuthContext.Provider
      value={{
        isLoggedIn: initialIsLoggedIn,
        user: initialUser ?? null,
        isAdmin,
      }}
    >
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