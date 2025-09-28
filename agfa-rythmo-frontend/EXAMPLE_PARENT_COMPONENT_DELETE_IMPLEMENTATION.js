// Exemple de code pour le composant parent qui utilise MultiRythmoBand

import { timecodeApi } from '@/api/timecodes'

// Dans la template du parent :
/*
<MultiRythmoBand
  :timecodes="timecodes"
  :currentTime="currentTime"
  :videoDuration="videoDuration"
  :sceneChanges="sceneChanges"
  :rythmoLinesCount="rythmoLinesCount"
  @delete-timecode="onDeleteTimecode"
  @seek="onSeek"
  @update-timecode="onUpdateTimecode"
  @update-timecode-bounds="onUpdateTimecodeBounds"
  @move-timecode="onMoveTimecode"
  @update-timecode-show-character="onUpdateTimecodeShowCharacter"
  @add-timecode-to-line="onAddTimecodeToLine"
  @update-lines-count="onUpdateLinesCount"
/>
*/

// Fonction à ajouter dans le composant parent :
async function onDeleteTimecode(payload: { timecode: Timecode }) {
  try {
    if (!payload.timecode.id || !props.project?.id) return

    // Appel API pour supprimer le timecode
    await timecodeApi.delete(props.project.id, payload.timecode.id)

    // Recharger les timecodes après suppression
    await loadTimecodes()

    // Optionnel : afficher un message de succès
    console.log('Timecode supprimé avec succès')
  } catch (error) {
    console.error('Erreur lors de la suppression du timecode:', error)
    // Optionnel : afficher un message d'erreur à l'utilisateur
  }
}

// La fonction loadTimecodes devrait ressembler à ça :
async function loadTimecodes() {
  if (!props.project?.id) return

  try {
    const response = await timecodeApi.getAll(props.project.id)
    timecodes.value = response.data.timecodes
  } catch (error) {
    console.error('Erreur lors du chargement des timecodes:', error)
  }
}