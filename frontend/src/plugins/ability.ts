import { createMongoAbility, type MongoAbility } from '@casl/ability'
import type { Action, Subject } from '@/types/api'

export type AppAbility = MongoAbility<[Action, Subject]>

// una sola instancia para toda la app; el store de sesión le va cambiando las reglas
export const ability: AppAbility = createMongoAbility<[Action, Subject]>()
