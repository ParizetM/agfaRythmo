import axios from './axios'

export interface User {
  id: number
  name: string
  email: string
  email_verified_at: string | null
  created_at: string
  updated_at: string
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface RegisterData {
  name: string
  email: string
  password: string
  password_confirmation: string
}

export interface AuthResponse {
  user: User
  token: string
  token_type: string
}

export interface ProfileUpdateData {
  name: string
  email: string
}

export interface PasswordChangeData {
  current_password: string
  password: string
  password_confirmation: string
}

class AuthService {
  async register(data: RegisterData): Promise<AuthResponse> {
    const response = await axios.post<AuthResponse>('/auth/register', data)
    return response.data
  }

  async login(credentials: LoginCredentials): Promise<AuthResponse> {
    const response = await axios.post<AuthResponse>('/auth/login', credentials)
    return response.data
  }

  async logout(): Promise<void> {
    await axios.post('/auth/logout')
  }

  async getProfile(): Promise<{ user: User }> {
    const response = await axios.get<{ user: User }>('/auth/profile')
    return response.data
  }

  async updateProfile(data: ProfileUpdateData): Promise<{ user: User; message: string }> {
    const response = await axios.put<{ user: User; message: string }>('/auth/profile', data)
    return response.data
  }

  async changePassword(data: PasswordChangeData): Promise<{ message: string }> {
    const response = await axios.post<{ message: string }>('/auth/change-password', data)
    return response.data
  }
}

export default new AuthService()
