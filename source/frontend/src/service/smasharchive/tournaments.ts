import { AxiosInstance } from 'axios';
import { Pagination } from './types';

export type Tournament = {
  id: number;
  slug: string;
  source: string;
  name: string;
  country?: {
    id: number;
    code: string;
    name: string;
  };
  region?: string;
  city?: string;
  location?: string;
  dateStart?: string;
  dateEnd?: string;
  timeZone?: string;
  playerCount?: number;
  isComplete: boolean;
};

export type TournamentResponse = {
  data: Tournament[];
  pagination: Pagination;
};

export default class Tournaments {
  constructor(private agent: AxiosInstance) {}

  public async getAll(
    limit: number,
    page: number,
    name: string | undefined,
    location: string | undefined,
  ): Promise<TournamentResponse> {
    let params: { [key: string]: string | number } = { limit, page };

    if (name) {
      params.name = name;
    }

    if (location) {
      params.location = location;
    }

    const response = await this.agent.get('/tournaments/', { params });

    return response.data;
  }
}
