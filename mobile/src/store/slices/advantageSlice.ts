import {createSlice, createAsyncThunk} from '@reduxjs/toolkit';
import {advantageService} from '@/services';
import {Advantage} from '@/types';

interface AdvantageState {
  advantages: Advantage[];
  favorites: Advantage[];
  recommendations: Advantage[];
  loading: boolean;
  error: string | null;
}

const initialState: AdvantageState = {
  advantages: [],
  favorites: [],
  recommendations: [],
  loading: false,
  error: null,
};

export const fetchAdvantages = createAsyncThunk(
  'advantages/fetchAdvantages',
  async (params?: any) => {
    const response = await advantageService.getAdvantages(params);
    return response.data?.data || [];
  },
);

export const fetchFavorites = createAsyncThunk(
  'advantages/fetchFavorites',
  async () => {
    const response = await advantageService.getFavorites();
    return response.data || [];
  },
);

const advantageSlice = createSlice({
  name: 'advantages',
  initialState,
  reducers: {},
  extraReducers: builder => {
    builder
      .addCase(fetchAdvantages.pending, state => {
        state.loading = true;
      })
      .addCase(fetchAdvantages.fulfilled, (state, action) => {
        state.loading = false;
        state.advantages = action.payload;
      })
      .addCase(fetchFavorites.fulfilled, (state, action) => {
        state.favorites = action.payload;
      });
  },
});

export default advantageSlice.reducer;
