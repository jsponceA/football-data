import type { Competition, Paginated, Player, Single, Team } from '@/types/api'
import { http } from './http'

interface ListParams {
  page?: number
  per_page?: number
  search?: string
}

export async function getCompetitions(params: ListParams = {}) {
  const { data } = await http.get<Paginated<Competition>>('/competitions', { params })
  return data
}

// consultar una liga hace que el backend la importe si hace falta, puede tardar
export async function getCompetition(id: number) {
  const { data } = await http.get<Single<Competition>>(`/competitions/${id}`)
  return data
}

export async function getTeams(params: ListParams & { competition?: number } = {}) {
  const { data } = await http.get<Paginated<Team>>('/team', { params })
  return data
}

export async function getTeam(id: number) {
  const { data } = await http.get<Single<Team>>(`/team/${id}`)
  return data.data
}

export async function getPlayers(params: ListParams & { team?: number } = {}) {
  const { data } = await http.get<Paginated<Player>>('/players', { params })
  return data
}
