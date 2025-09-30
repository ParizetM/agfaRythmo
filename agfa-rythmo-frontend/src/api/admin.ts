import axios from './axios'
import type { User } from './auth'

export interface AdminStats {
  total_users: number
  admin_users: number
  regular_users: number
  total_projects: number
  projects_with_collaborators: number
}

export interface Project {
  id: number
  name: string
  description?: string
  video_path?: string
  user_id: number
  created_at: string
  updated_at: string
  owner?: User
  collaborators?: User[]
}

export interface UserWithStats extends User {
  stats?: {
    owned_projects: number
    collaborative_projects: number
  }
}

export interface CreateUserData {
  name: string
  email: string
  password: string
  role: 'admin' | 'user'
}

export interface UpdateUserData {
  name: string
  email: string
  role: 'admin' | 'user'
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

class AdminService {
  // Statistiques globales
  async getStats(): Promise<AdminStats> {
    const response = await axios.get<AdminStats>('/admin/stats')
    return response.data
  }

  // Gestion des utilisateurs
  async getUsers(params?: { search?: string; role?: string; page?: number }): Promise<PaginatedResponse<UserWithStats>> {
    const response = await axios.get<PaginatedResponse<UserWithStats>>('/admin/users', { params })
    return response.data
  }

  async getUser(id: number): Promise<{ user: UserWithStats }> {
    const response = await axios.get<{ user: UserWithStats }>(`/admin/users/${id}`)
    return response.data
  }

  async createUser(data: CreateUserData): Promise<{ user: User; message: string }> {
    const response = await axios.post<{ user: User; message: string }>('/admin/users', data)
    return response.data
  }

  async updateUser(id: number, data: UpdateUserData): Promise<{ user: User; message: string }> {
    const response = await axios.put<{ user: User; message: string }>(`/admin/users/${id}`, data)
    return response.data
  }

  async changeUserPassword(id: number, password: string): Promise<{ message: string }> {
    const response = await axios.post<{ message: string }>(`/admin/users/${id}/change-password`, {
      password
    })
    return response.data
  }

  async deleteUser(id: number): Promise<{ message: string }> {
    const response = await axios.delete<{ message: string }>(`/admin/users/${id}`)
    return response.data
  }

  // Gestion des projets (vue admin)
  async getAllProjects(params?: { search?: string; owner_id?: number; page?: number }): Promise<PaginatedResponse<Project>> {
    const response = await axios.get<PaginatedResponse<Project>>('/admin/projects', { params })
    return response.data
  }
}

export default new AdminService()
